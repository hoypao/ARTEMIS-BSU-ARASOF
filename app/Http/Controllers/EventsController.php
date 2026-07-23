<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Ports of events.php (public calendar page) and
 * actions/events/rsvp_action.php (student RSVP JSON endpoint).
 */
class EventsController extends Controller
{
    /** events.php */
    public function index()
    {
        $user = current_user();
        $pdo = getDB();

        $events = $pdo->query(
            'SELECT e.*, t.name AS requires_type_name FROM events e
             LEFT JOIN application_types t ON t.type_id = e.requires_application_type_id
             ORDER BY e.event_date ASC'
        )->fetchAll();

        $countsByEvent = [];
        $counts = $pdo->query(
            "SELECT event_id, SUM(status IN ('Registered','Attended')) AS registered FROM event_attendance GROUP BY event_id"
        )->fetchAll();
        foreach ($counts as $row) {
            $countsByEvent[$row['event_id']] = (int) $row['registered'];
        }

        $myAttendance = [];
        $myApprovedTypeIds = [];
        if ($user && $user['role'] === 'student') {
            $stmt = $pdo->prepare('SELECT event_id, status FROM event_attendance WHERE user_id = :id');
            $stmt->execute(['id' => $user['user_id']]);
            foreach ($stmt->fetchAll() as $row) {
                $myAttendance[$row['event_id']] = $row['status'];
            }
            $myApprovedTypeIds = get_user_approved_application_type_ids($pdo, $user['user_id']);
        }

        $fallbackImages = [
            'https://images.unsplash.com/photo-1747155827634-012749f4eca1?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHx0aGVhdGVyJTIwYXJ0cyUyMHN0YWdlJTIwZHJhbWElMjBzdHVkZW50c3xlbnwxfHx8fDE3NzQ5NjAwNDd8MA&ixlib=rb-4.1.0&q=80&w=1080',
        ];

        $eventsData = [];
        foreach ($events as $ev) {
            $ts = strtotime($ev['event_date']);
            $eventsData[] = [
                'id' => (int) $ev['event_id'],
                'title' => $ev['title'],
                'date' => format_date($ev['event_date'], 'F j, Y'),
                'month' => date('F', $ts),
                'day' => (string) date('j', $ts),
                'time' => $ev['event_time'] ? date('g:i A', strtotime($ev['event_time'])) : '',
                'location' => $ev['location'] ?: '',
                'type' => $ev['event_type'] ?: 'Event',
                'status' => $ev['status'],
                'description' => $ev['description'] ?: '',
                'audience' => $ev['audience'] ?: 'All Students',
                'expectedAttendees' => (int) $ev['expected_attendees'],
                'registered' => $countsByEvent[$ev['event_id']] ?? 0,
                'image' => $ev['image_path'] ?: $fallbackImages[0],
                'color' => $ev['color_hex'] ?: '#B11226',
                'myStatus' => $myAttendance[$ev['event_id']] ?? null,
                'requiresTravel' => (bool) $ev['requires_travel'],
                'requiresTypeName' => $ev['requires_type_name'],
                'eligible' => $ev['requires_application_type_id'] === null || isset($myApprovedTypeIds[(int) $ev['requires_application_type_id']]),
                'closingSoon' => $ts >= strtotime('today') && $ts <= strtotime('+3 days') && $ev['status'] === 'Upcoming',
            ];
        }

        $eventTypes = array_values(array_unique(array_column($eventsData, 'type')));
        sort($eventTypes);
        $months = array_values(array_unique(array_column($eventsData, 'month')));

        $portalUrl = route('login');
        if ($user) {
            $portalUrl = route($user['role'] === 'admin' ? 'admin.dashboard' : 'student.dashboard');
        }

        $pageTitle = 'Events';

        return view('pages.events', get_defined_vars());
    }

    /** actions/events/rsvp_action.php (JSON, role:student via route middleware) */
    public function rsvp(Request $request)
    {
        $input = $request->json()->all() ?: [];

        $eventId = (int) ($input['event_id'] ?? 0);
        $action = $input['action'] ?? '';
        $travelAcknowledged = !empty($input['travel_acknowledged']);
        $user = current_user();

        if (!in_array($action, ['register', 'cancel'], true) || $eventId <= 0) {
            return response()->json(['error' => 'Invalid request.'], 400);
        }

        $pdo = getDB();

        $stmt = $pdo->prepare(
            'SELECT e.status, e.requires_travel, e.requires_application_type_id, t.name AS requires_type_name
             FROM events e LEFT JOIN application_types t ON t.type_id = e.requires_application_type_id
             WHERE e.event_id = :id'
        );
        $stmt->execute(['id' => $eventId]);
        $event = $stmt->fetch();

        if (!$event) {
            return response()->json(['error' => 'Event not found.'], 404);
        }
        if ($action === 'register' && in_array($event['status'], ['Completed', 'Cancelled'], true)) {
            return response()->json(['error' => 'RSVPs are closed for this event.'], 409);
        }

        // "By Invitation" events are really "approved applicants of a specific type
        // only". Enforced server-side so it can't be skipped by calling this directly.
        if ($action === 'register' && $event['requires_application_type_id'] !== null) {
            $approvedTypeIds = get_user_approved_application_type_ids($pdo, $user['user_id']);
            if (!isset($approvedTypeIds[(int) $event['requires_application_type_id']])) {
                return response()->json(['error' => 'This event is limited to approved ' . $event['requires_type_name'] . ' applicants. Registration is not available unless your application has been approved.'], 403);
            }
        }

        // Off-campus events require the travel acknowledgment before the RSVP is
        // recorded — enforced server-side, same as the legacy gate.
        if ($action === 'register' && (int) $event['requires_travel'] === 1 && !$travelAcknowledged) {
            return response()->json(['error' => 'This event requires off-campus travel. Please acknowledge the travel terms to register.', 'requires_travel' => true], 409);
        }

        if ($action === 'register') {
            $travelAcknowledgedAt = ((int) $event['requires_travel'] === 1) ? date('Y-m-d H:i:s') : null;
            // Re-registering after a prior cancel flips the same row back to Registered
            // instead of erroring on the (event_id, user_id) unique constraint.
            $stmt = $pdo->prepare(
                'INSERT INTO event_attendance (event_id, user_id, status, travel_acknowledged_at)
                 VALUES (:event_id, :user_id, "Registered", :ack)
                 ON DUPLICATE KEY UPDATE status = "Registered", registered_at = CURRENT_TIMESTAMP, travel_acknowledged_at = :ack2'
            );
            $stmt->execute(['event_id' => $eventId, 'user_id' => $user['user_id'], 'ack' => $travelAcknowledgedAt, 'ack2' => $travelAcknowledgedAt]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE event_attendance SET status = "Cancelled" WHERE event_id = :event_id AND user_id = :user_id'
            );
            $stmt->execute(['event_id' => $eventId, 'user_id' => $user['user_id']]);
        }

        return response()->json(['success' => true]);
    }
}

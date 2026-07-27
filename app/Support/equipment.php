<?php
/**
 * Equipment & Materials Inventory / Procurement (Art. VII; Art. III Sec. 6.C —
 * the Property Custodian's duties). Constants + small pure helpers; the
 * actual reads/writes live in AdminOpsController::equipmentItems() and
 * ::procurementRequests(), same split as every other Support file here.
 */

const ARTEMIS_EQUIPMENT_CATEGORIES = ['Music', 'Dance', 'Theater', 'Film', 'Visual Arts', 'General'];

const ARTEMIS_EQUIPMENT_CONDITIONS = ['Good', 'Needs Repair', 'Damaged', 'Lost'];

const ARTEMIS_PROCUREMENT_STATUSES = ['Draft', 'PPMP Submitted', 'PR Prepared', 'Approved', 'Delivered', 'Cancelled'];

// Phase 2 (Sec. 20-C — cross-campus resource sharing; RLSDDP loss/damage reporting).
const ARTEMIS_CAMPUSES = ['ARASOF-Nasugbu', 'Alangilan', 'Pablo Borbon', 'Malvar', 'Lipa', 'Other'];

const ARTEMIS_SHARE_STATUSES = ['Requested', 'Approved', 'In Transit', 'Returned', 'Declined'];

const ARTEMIS_LOSS_REPORT_TYPES = ['Lost', 'Stolen', 'Damaged', 'Destroyed'];

const ARTEMIS_LOSS_REPORT_STATUSES = ['Reported', 'Under Investigation', 'Resolved'];

/**
 * Filing an RLSDDP report is what keeps the inventory's condition field
 * honest (Sec. 20-C.2 — damage/malfunction "must be reported immediately"),
 * so saving a loss report also updates its equipment_item's condition
 * automatically instead of requiring the same fact to be entered twice.
 * 'Damaged' maps straight across; 'Lost'/'Stolen'/'Destroyed' all collapse
 * to the closest existing condition_status value, 'Lost', since the
 * inventory's condition enum (Phase 1) only has four states.
 */
function equipment_condition_for_loss_report(string $reportType): string
{
    return $reportType === 'Damaged' ? 'Damaged' : 'Lost';
}

/**
 * Small summary strip for the top of the Equipment & Materials tab — counts
 * of on-hand items by condition. Pure function over an already-fetched
 * equipment_items result set (no PDO), same shape as how the view already
 * gets PARTNERS/APPEALS/etc. pre-aggregated in AdminDashboardController.
 *
 * @param array<int, array<string, mixed>> $items
 * @return array<string, int>
 */
function summarize_equipment_conditions(array $items): array
{
    $counts = array_fill_keys(ARTEMIS_EQUIPMENT_CONDITIONS, 0);
    foreach ($items as $item) {
        $condition = $item['condition_status'] ?? 'Good';
        if (isset($counts[$condition])) {
            $counts[$condition]++;
        }
    }
    return $counts;
}

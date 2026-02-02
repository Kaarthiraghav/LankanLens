<?php
/**
 * Audit Logger Helper
 * Logs admin actions to admin_logs table for accountability and auditing
 */

/**
 * Log an admin action to the audit trail
 * 
 * @param Database $db Database instance
 * @param int $admin_user_id ID of the admin performing the action
 * @param string $action_type Type of action (CREATE, UPDATE, DELETE, APPROVE, REJECT, etc.)
 * @param int|null $target_user_id ID of the user affected by the action (if applicable)
 * @param int|null $target_equipment_id ID of the equipment affected by the action
 * @param array $details Additional details about the action (before/after values, reason, etc.)
 * @return bool Success status
 */
function logAdminAction($db, $admin_user_id, $action_type, $target_user_id = null, $target_equipment_id = null, $details = []) {
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $details_json = json_encode($details, JSON_UNESCAPED_SLASHES);

        $db->query(
            "INSERT INTO admin_logs (admin_user_id, action_type, target_user_id, target_equipment_id, action_details, ip_address)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$admin_user_id, $action_type, $target_user_id, $target_equipment_id, $details_json, $ip_address]
        );

        return true;
    } catch (Exception $e) {
        error_log("Audit logging failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get formatted audit logs for display
 * 
 * @param Database $db Database instance
 * @param int $limit Number of logs to retrieve
 * @param int $offset Pagination offset
 * @return array Array of audit log records with admin names
 */
function getAuditLogs($db, $limit = 50, $offset = 0) {
    try {
        return $db->fetchAll(
            "SELECT 
                al.log_id, 
                al.admin_user_id, 
                u.full_name AS admin_name, 
                al.action_type, 
                al.target_user_id, 
                al.target_equipment_id, 
                e.equipment_name, 
                e.brand, 
                e.model_number,
                al.action_details, 
                al.ip_address, 
                al.created_at
             FROM admin_logs al
             LEFT JOIN users u ON u.user_id = al.admin_user_id
             LEFT JOIN equipment e ON e.equipment_id = al.target_equipment_id
             ORDER BY al.created_at DESC
             LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    } catch (Exception $e) {
        error_log("Failed to retrieve audit logs: " . $e->getMessage());
        return [];
    }
}

/**
 * Get audit logs for a specific equipment item
 * 
 * @param Database $db Database instance
 * @param int $equipment_id Equipment to track
 * @return array Array of audit log records for the equipment
 */
function getEquipmentAuditLogs($db, $equipment_id) {
    try {
        return $db->fetchAll(
            "SELECT 
                al.log_id, 
                al.admin_user_id, 
                u.full_name AS admin_name, 
                al.action_type, 
                al.action_details, 
                al.ip_address, 
                al.created_at
             FROM admin_logs al
             LEFT JOIN users u ON u.user_id = al.admin_user_id
             WHERE al.target_equipment_id = ?
             ORDER BY al.created_at DESC",
            [$equipment_id]
        );
    } catch (Exception $e) {
        error_log("Failed to retrieve equipment audit logs: " . $e->getMessage());
        return [];
    }
}

/**
 * Get total count of audit logs (for pagination)
 * 
 * @param Database $db Database instance
 * @return int Total count
 */
function getAuditLogCount($db) {
    try {
        $result = $db->fetchOne("SELECT COUNT(*) AS total FROM admin_logs");
        return $result ? (int)$result['total'] : 0;
    } catch (Exception $e) {
        error_log("Failed to count audit logs: " . $e->getMessage());
        return 0;
    }
}
?>

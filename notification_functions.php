<?php
require_once "db.php";

/*
|--------------------------------------------------------------------------
| Send Notification
|--------------------------------------------------------------------------
*/
function sendNotification($conn, $user_id, $role, $message, $appointment_id = null, $type = "appointment")
{
    $stmt = $conn->prepare("
        INSERT INTO notifications 
        (user_id, role, appointment_id, message, type)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "isiss",
        $user_id,
        $role,
        $appointment_id,
        $message,
        $type
    );

    return $stmt->execute();
}


/*
|--------------------------------------------------------------------------
| Get All Notifications
|--------------------------------------------------------------------------
*/
function getNotifications($conn, $user_id)
{
    $stmt = $conn->prepare("
        SELECT * 
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    return $stmt->get_result();
}


/*
|--------------------------------------------------------------------------
| Get Unread Notification Count
|--------------------------------------------------------------------------
*/
function getUnreadCount($conn, $user_id)
{
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total
        FROM notifications
        WHERE user_id = ? 
        AND is_read = 0
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();

    return $result->fetch_assoc()['total'];
}


/*
|--------------------------------------------------------------------------
| Mark Single Notification As Read
|--------------------------------------------------------------------------
*/
function markNotificationRead($conn, $notification_id)
{
    $stmt = $conn->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE id = ?
    ");

    $stmt->bind_param("i", $notification_id);

    return $stmt->execute();
}


/*
|--------------------------------------------------------------------------
| Mark All Notifications As Read
|--------------------------------------------------------------------------
*/
function markAllNotificationsRead($conn, $user_id)
{
    $stmt = $conn->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE user_id = ?
    ");

    $stmt->bind_param("i", $user_id);

    return $stmt->execute();
}
?>
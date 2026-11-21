<?php


function getCount($conn, $sql) {
    $result = $conn->query($sql);

    if ($result) {
        return $result->fetch_assoc()['total'] ?? 0;
    }

    return 0;
}

function generateOrderNumber($conn) {
    $datePart = date("Ymd");
    $prefix = "ORD-" . $datePart . "-";

    $sql = "SELECT COUNT(*) AS total
            FROM orders
            WHERE DATE(created_at) = CURDATE()";

    $result = $conn->query($sql);
    $count = $result ? (int)$result->fetch_assoc()['total'] : 0;

    $nextNumber = str_pad($count + 1, 3, "0", STR_PAD_LEFT);

    return $prefix . $nextNumber;
}


function getOrderStatusBadge($status) {
    return match ($status) {
        'Pending' => 'warning',
        'Accepted' => 'info',
        'Preparing' => 'primary',
        'Ready' => 'success',
        'Completed' => 'success',
        'Cancelled' => 'danger',
        default => 'secondary'
    };
}


function getPaymentStatusBadge($status) {
    return $status === 'Paid' ? 'success' : 'danger';
}
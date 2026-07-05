<?php
namespace Middleware;

class CustomerAuth {
    public function handle() {
        if (empty($_SESSION['customer_id'])) {
            header("Location: /login");
            exit;
        }
    }
}
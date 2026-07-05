<?php
namespace Controllers;

class BookingController
{
    public function index()
    {
        require_once __DIR__ . '/../Views/layouts/app_layout.php';
        renderAppLayout('Booking', __DIR__ . '/../Views/public/booking.php');
    }
}
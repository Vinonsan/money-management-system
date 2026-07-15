# MasjidPay

Cash collection system for masjid donations / collections.

## Local URL

`http://localhost/masjidpay`

## Structure

```
masjidpay/
├── config/          # App + DB config
├── public/          # Front controller + assets
├── src/
│   ├── Controllers/
│   ├── Models/
│   ├── Middleware/
│   ├── Views/
│   ├── Components/
│   ├── Helpers/
│   └── Services/
├── deploy/
├── schema.sql
└── setup.php
```

## Setup

1. Start Apache + MySQL in XAMPP
2. Open `http://localhost/masjidpay/setup.php` once to create the DB
3. Develop new features under `src/`

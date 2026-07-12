<?php
namespace Controllers;

use Models\Location;
use Models\User;
use Models\Ward;

class AdminController
{
    public function index(): void
    {
        $this->view('Dashboard', 'dashboard.php', [], 'dashboard');
    }

    public function profile(): void
    {
        $user = User::findById((int) ($_SESSION['user_id'] ?? 0));
        $this->view('Profile', 'profile.php', ['user' => $user ?? []], 'profile');
    }

    // ──────────────────────────────────────────────
    //  Location Management
    // ──────────────────────────────────────────────

    public function locations(): void
    {
        $page     = max(1, (int) ($_GET['page'] ?? 1));
        $perPage  = max(1, min(100, (int) ($_GET['per_page'] ?? 10)));
        $search   = trim((string) ($_GET['search'] ?? ''));
        $filterBy = trim((string) ($_GET['filter'] ?? ''));
        $sortField = $_GET['sort'] ?? 'id';
        $sortDir  = $_GET['dir'] ?? 'asc';

        $locations = Location::getAll($page, $perPage, $search, $sortField, $sortDir, $filterBy);
        $total     = Location::count($search, $filterBy);

        $this->view('Location', 'locations.php', [
            'locations'  => $locations,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'search'     => $search,
            'filterBy'   => $filterBy,
            'sortField'  => $sortField,
            'sortDir'    => $sortDir,
        ], 'locations.location');
    }

    public function createLocation(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        if (empty($data['name']) || trim($data['name']) === '') {
            $this->jsonError('Location name is required.');
            return;
        }

        try {
            Location::create([
                'name'      => trim($data['name']),
                'address'   => trim($data['address'] ?? ''),
                'city'      => trim($data['city'] ?? ''),
                'is_active' => !empty($data['is_active']) ? 1 : 0,
            ]);
            $this->jsonSuccess('Location created.');
        } catch (\Exception $e) {
            $this->jsonError('Failed to create location: ' . $e->getMessage());
        }
    }

    public function updateLocation(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Invalid location ID.');
            return;
        }

        if (empty($data['name']) || trim($data['name']) === '') {
            $this->jsonError('Location name is required.');
            return;
        }

        try {
            Location::update($id, [
                'name'      => trim($data['name']),
                'address'   => trim($data['address'] ?? ''),
                'city'      => trim($data['city'] ?? ''),
                'is_active' => !empty($data['is_active']) ? 1 : 0,
            ]);
            $this->jsonSuccess('Location updated.');
        } catch (\Exception $e) {
            $this->jsonError('Failed to update location: ' . $e->getMessage());
        }
    }

    public function deleteLocation(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Invalid location ID.');
            return;
        }

        try {
            Location::delete($id);
            $this->jsonSuccess('Location deleted.');
        } catch (\Exception $e) {
            $this->jsonError('Failed to delete location: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────
    //  Wards
    // ──────────────────────────────────────────────

    public function wards(): void
    {
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 50;

        $wards = Ward::getAll($page, $perPage);
        $total = Ward::count();
        $locations = Location::allActive();

        $this->view('Ward', 'wards.php', [
            'wards'     => $wards,
            'total'     => $total,
            'page'      => $page,
            'perPage'   => $perPage,
            'locations' => $locations,
        ], 'locations.ward');
    }

    public function createWard(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        if (!isset($data['ward_number']) || filter_var($data['ward_number'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            $this->jsonError('Ward number must be a positive number.');
            return;
        }
        $locationIds = $this->locationIds($data['location_ids'] ?? []);
        if (empty($locationIds)) {
            $this->jsonError('Select at least one location.');
            return;
        }
        if (Ward::numberExists((int) $data['ward_number'])) {
            $this->jsonError('This ward number already exists.');
            return;
        }

        try {
            $wardId = Ward::create([
                'ward_number' => (int) $data['ward_number'],
                'is_active'   => !empty($data['is_active']) ? 1 : 0,
            ]);
            Ward::syncLocations((int) $wardId, $locationIds);
            $this->jsonSuccess('Ward created.');
        } catch (\Exception $e) {
            $this->jsonError('Failed to create ward: ' . $e->getMessage());
        }
    }

    public function updateWard(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Invalid ward ID.');
            return;
        }
        if (!isset($data['ward_number']) || filter_var($data['ward_number'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            $this->jsonError('Ward number must be a positive number.');
            return;
        }
        $locationIds = $this->locationIds($data['location_ids'] ?? []);
        if (empty($locationIds)) {
            $this->jsonError('Select at least one location.');
            return;
        }
        if (Ward::numberExists((int) $data['ward_number'], $id)) {
            $this->jsonError('This ward number already exists.');
            return;
        }

        try {
            Ward::update($id, [
                'ward_number' => (int) $data['ward_number'],
                'is_active'   => !empty($data['is_active']) ? 1 : 0,
            ]);
            Ward::syncLocations($id, $locationIds);
            $this->jsonSuccess('Ward updated.');
        } catch (\Exception $e) {
            $this->jsonError('Failed to update ward: ' . $e->getMessage());
        }
    }

    public function deleteWard(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Invalid ward ID.');
            return;
        }

        try {
            Ward::delete($id);
            $this->jsonSuccess('Ward deleted.');
        } catch (\Exception $e) {
            $this->jsonError('Failed to delete ward: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────
    //  Users
    // ──────────────────────────────────────────────

    public function usersList(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 10)));
        $search = trim((string) ($_GET['search'] ?? ''));
        $locationId = max(0, (int) ($_GET['location_id'] ?? 0));
        $wardId = max(0, (int) ($_GET['ward_id'] ?? 0));
        $sortField = (string) ($_GET['sort'] ?? 'created_at');
        $sortDir = (string) ($_GET['dir'] ?? 'desc');
        $total = User::count($search, $locationId, $wardId);

        $this->view('Users', 'users.php', [
            'users' => User::getAll($page, $perPage, $search, $sortField, $sortDir, $locationId, $wardId),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'search' => $search,
            'locationId' => $locationId,
            'wardId' => $wardId,
            'sortField' => $sortField,
            'sortDir' => $sortDir,
            'locations' => Location::allActive(),
            'wards' => Ward::getAll(1, 999),
        ], 'users.list');
    }

    public function createUser(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();
        $name = trim((string) ($data['name'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));

        if ($name === '' || $phone === '') {
            $this->jsonError('Name and phone number are required.');
            return;
        }
        if (!preg_match('/^[+0-9][+0-9()\\- ]{6,19}$/', $phone)) {
            $this->jsonError('Enter a valid phone number.');
            return;
        }
        if (User::phoneExists($phone)) {
            $this->jsonError('This phone number is already in use.');
            return;
        }

        try {
            User::create([
                'name' => $name,
                'phone' => $phone,
                'location_id' => !empty($data['location_id']) ? (int) $data['location_id'] : null,
                'ward_id' => !empty($data['ward_id']) ? (int) $data['ward_id'] : null,
                'monthly_amount' => !empty($data['monthly_amount']) ? (float) $data['monthly_amount'] : 0,
            ]);
            $this->jsonSuccess('User created.');
        } catch (\Exception $e) {
            $this->jsonError('Failed to create user: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────
    //  Internal helpers
    // ──────────────────────────────────────────────

    private function placeholder(string $title, string $activeNav, string $hint = ''): void
    {
        $this->view($title, 'placeholder.php', [
            'pageTitle' => $title,
            'pageHint'  => $hint !== '' ? $hint : 'Module UI coming next.',
        ], $activeNav);
    }

    private function view(string $title, string $file, array $data, string $activeNav): void
    {
        require_once __DIR__ . '/../Views/layouts/app_layout.php';
        renderAppLayout($title, __DIR__ . '/../Views/admin/' . $file, $data, $activeNav);
    }

    private function requireJson(): void
    {
        if (strtolower($_SERVER['REQUEST_METHOD'] ?? '') !== 'post') {
            http_response_code(405);
            $this->jsonError('Method not allowed.');
            exit;
        }
    }

    private function jsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private function jsonSuccess(string $message): void
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => $message]);
        exit;
    }

    private function locationIds(mixed $locationIds): array
    {
        if (!is_array($locationIds)) {
            return [];
        }

        $ids = array_values(array_unique(array_filter(
            array_map('intval', $locationIds),
            static fn (int $id): bool => $id > 0
        )));
        $activeIds = array_map('intval', array_column(Location::allActive(), 'id'));

        return count($ids) === count(array_intersect($ids, $activeIds)) ? $ids : [];
    }

    private function jsonError(string $message): void
    {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $message]);
        exit;
    }
}

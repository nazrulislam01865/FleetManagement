<?php

$vehicleTypes = ['Microbus', 'Van', 'Pickup', 'Sedan', 'Bus', 'SUV'];
$vehicleNames = ['Toyota Noah', 'Nissan Caravan', 'Mitsubishi Pickup', 'Hyundai H1', 'Toyota Hiace', 'Tata Bus', 'Toyota Axio', 'Toyota X Corolla', 'Suzuki APV', 'Mahindra Bolero'];
$driverAreas = ['Dhaka', 'Gazipur', 'Narayanganj', 'Chattogram', 'Cumilla', 'Mymensingh'];
$firstNames = ['Kamal', 'Jahangir', 'Shafiqur', 'Mizanur', 'Rashid', 'Habib', 'Rafiq', 'Sumon', 'Masud', 'Rakib', 'Sabbir', 'Sajal', 'Biplob', 'Sultan', 'Kawsar', 'Faruk', 'Anis', 'Mamun'];
$lastNames = ['Hossain', 'Alam', 'Rahman', 'Islam', 'Ahmed', 'Mia', 'Kabir', 'Hasan', 'Uddin', 'Karim'];

$tripVehicles = [];
for ($i = 0; $i < 36; $i++) {
    $tripVehicles[] = [
        'id' => 'VHL2606' . (100 + $i),
        'name' => $vehicleNames[$i % count($vehicleNames)] . ' ' . ($i + 1),
        'type' => $vehicleTypes[$i % count($vehicleTypes)],
        'note' => $i % 2 === 0 ? 'Available for regular duty' : 'Available for backup / long route',
    ];
}

$tripDrivers = [];
for ($i = 0; $i < 72; $i++) {
    $tripDrivers[] = [
        'id' => 'DVR2606' . (100 + $i),
        'name' => $firstNames[$i % count($firstNames)] . ' ' . $lastNames[$i % count($lastNames)],
        'phone' => '01' . substr((string) (700000000 + $i), 0, 9),
        'area' => $driverAreas[$i % count($driverAreas)],
    ];
}


$fuelRechargeContracts = [
    'CN-2026-011 | Metro Logistics',
    'CN-2026-017 | City Transport Support',
    'CN-2026-021 | North Zone Delivery',
];
$fuelRechargeCars = [
    'Dhaka Metro-SA-12-0117',
    'Dhaka Metro-TA-19-3364',
    'Dhaka Metro-GA-22-2210',
    'Dhaka Metro-KHA-18-4302',
    'Dhaka Metro-NA-14-1456',
    'Dhaka Pickup 01',
    'Airport Van 02',
];
$fuelRechargeDrivers = [
    'Md. Karim',
    'Shahidul Islam',
    'Rafiq Ahmed',
    'Kamal Hossain',
    'Jahangir Alam',
    'Shafiqur Rahman',
    'Mizanur Rahman',
];
$fuelRechargeSamples = [];
$fuelRechargeDates = [];
foreach (range(1, 31) as $day) {
    $fuelRechargeDates[] = '2026-05-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT);
}
foreach (range(1, 3) as $day) {
    $fuelRechargeDates[] = '2026-06-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT);
}
foreach ($fuelRechargeDates as $dateIndex => $date) {
    foreach (range(0, 4) as $vehicleIndex) {
        if ((($dateIndex + $vehicleIndex) % 4) === 0) {
            continue;
        }
        $diesel = $vehicleIndex === 1 ? 0 : round(18 + (($dateIndex + $vehicleIndex) % 12) * 2.35, 2);
        $octane = $vehicleIndex === 1 ? round(9 + (($dateIndex + $vehicleIndex) % 6) * 1.75, 2) : (($vehicleIndex % 3) === 0 ? round(2 + ($dateIndex % 5) * 0.9, 2) : 0);
        $gas = ($vehicleIndex % 2) === 0 ? round(420 + (($dateIndex * 83 + $vehicleIndex * 137) % 1800), 2) : 0;
        $startKm = 124500 + ($dateIndex * 82) + ($vehicleIndex * 725);
        $totalKm = (int) round(($diesel * 8.5) + ($octane * 6.8) + (($dateIndex + $vehicleIndex) % 5) * 12);
        $endKm = $startKm + $totalKm;
        $totalFuel = max($diesel + $octane, 1);
        $fuelRechargeSamples[] = [
            'rechargeId' => 'FR-' . str_replace('-', '', $date) . '-' . str_pad((string) ($vehicleIndex + 1), 2, '0', STR_PAD_LEFT),
            'date' => $date,
            'contract' => $fuelRechargeContracts[$vehicleIndex % count($fuelRechargeContracts)],
            'vehicle' => $fuelRechargeCars[$vehicleIndex % count($fuelRechargeCars)],
            'car' => $fuelRechargeCars[$vehicleIndex % count($fuelRechargeCars)],
            'driver' => $fuelRechargeDrivers[$vehicleIndex % count($fuelRechargeDrivers)],
            'driverStart' => str_pad((string) (7 + ($vehicleIndex % 3)), 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) (($dateIndex * 10) % 60), 2, '0', STR_PAD_LEFT),
            'driverEnd' => str_pad((string) (16 + ($vehicleIndex % 4)), 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) (($dateIndex * 10) % 60), 2, '0', STR_PAD_LEFT),
            'totalTime' => round(7.5 + (($dateIndex + $vehicleIndex) % 5) * 0.5, 2),
            'diesel' => $diesel,
            'gas' => $gas,
            'octane' => $octane,
            'startKm' => $startKm,
            'endKm' => $endKm,
            'totalKm' => $totalKm,
            'mileage' => round($totalKm / $totalFuel, 2),
            'status' => (($dateIndex + $vehicleIndex) % 9) === 0 ? 'Draft' : 'Submitted',
            'submittedBy' => ['Admin User', 'Field Officer', 'Supervisor'][$vehicleIndex % 3],
            'fuelType' => $diesel > 0 && $gas > 0 ? 'Diesel + CNG/LPG' : ($diesel > 0 ? 'Diesel' : 'Octane'),
        ];
    }
}

return [
    'inactivity_timeout_minutes' => 15,

    'uploads' => [
        'documents' => [
            'chunk_bytes' => 262144,
        ],
    ],

    'brand' => [
        'name' => 'FleetMan',
        'tagline' => 'Fleet Management System',
        'footer_owner' => 'ITQAN Consulting',
    ],

    'account' => [
        'title' => 'My Account',
        'name' => 'Ashish',
        'avatar' => '👤',
    ],

    'menu' => [
        [
            'title' => 'Operations',
            'items' => [
                ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => '🏠', 'route' => 'fleet.dashboard', 'permission' => 'dashboard.view'],
                [
                    'key' => 'trips',
                    'label' => 'Trips',
                    'icon' => '🧭',
                    'route' => 'fleet.trips',
                    'permission' => 'trips.view',
                    'children' => [
                        ['key' => 'trips-add', 'label' => 'Add Trip', 'icon' => '↳', 'route' => 'fleet.trips', 'routeParams' => ['action' => 'add'], 'permission' => 'trips.manage'],
                        ['key' => 'trips-list', 'label' => 'Trip List', 'icon' => '↳', 'route' => 'fleet.trips', 'routeParams' => ['action' => 'list'], 'permission' => 'trips.view'],
                    ]
                ],
                [
                    'key' => 'drive-log',
                    'label' => 'Logs',
                    'icon' => '📝',
                    'route' => 'fleet.driver-attendance',
                    'permission' => 'driver_attendance.view',
                    'children' => [
                        ['key' => 'logs-add', 'label' => 'Add Log', 'icon' => '↳', 'route' => 'fleet.driver-attendance', 'routeParams' => ['action' => 'add'], 'permission' => 'driver_attendance.manage'],
                        ['key' => 'logs-list', 'label' => 'Log List', 'icon' => '↳', 'route' => 'fleet.driver-attendance', 'routeParams' => ['action' => 'list'], 'permission' => 'driver_attendance.view'],
                    ]
                ],
            ],
        ],
        [
            'title' => 'Fleet Management',
            'items' => [
                [
                    'key' => 'yards',
                    'label' => 'Yards',
                    'icon' => '🅿️',
                    'route' => 'fleet.yards',
                    'permission' => 'yards.view',
                    'children' => [
                        ['key' => 'yards-add', 'label' => 'Add Yard', 'icon' => '↳', 'route' => 'fleet.yards', 'routeParams' => ['action' => 'add'], 'permission' => 'yards.manage'],
                        ['key' => 'yards-list', 'label' => 'Yard List', 'icon' => '↳', 'route' => 'fleet.yards', 'routeParams' => ['action' => 'list'], 'permission' => 'yards.view'],
                    ]
                ],
                [
                    'key' => 'vehicles',
                    'label' => 'Vehicles',
                    'icon' => '🚚',
                    'route' => 'fleet.vehicles',
                    'permission' => 'vehicles.view',
                    'children' => [
                        ['key' => 'vehicles-add', 'label' => 'Add Vehicle', 'icon' => '↳', 'route' => 'fleet.vehicles', 'routeParams' => ['action' => 'add'], 'permission' => 'vehicles.manage'],
                        ['key' => 'vehicles-list', 'label' => 'Vehicle List', 'icon' => '↳', 'route' => 'fleet.vehicles', 'routeParams' => ['action' => 'list'], 'permission' => 'vehicles.view'],
                    ]
                ],
                [
                    'key' => 'fuel-recharge',
                    'label' => 'Fuel',
                    'icon' => '⛽',
                    'route' => 'fleet.fuel-recharge',
                    'permission' => 'fuel_recharge.view',
                    'children' => [
                        ['key' => 'recharge-add', 'label' => 'Add Fuel', 'icon' => '↳', 'route' => 'fleet.fuel-recharge', 'routeParams' => ['action' => 'add'], 'permission' => 'fuel_recharge.manage'],
                        ['key' => 'recharge-list', 'label' => 'Recharge List', 'icon' => '↳', 'route' => 'fleet.fuel-recharge', 'routeParams' => ['action' => 'list'], 'permission' => 'fuel_recharge.view'],
                    ]
                ],
                [
                    'key' => 'fuel-prices',
                    'label' => 'Fuel Prices',
                    'icon' => '🏷️',
                    'route' => 'fleet.fuel-prices',
                    'permission' => 'fuel_prices.view',
                    'children' => [
                        ['key' => 'prices-add', 'label' => 'Add Price', 'icon' => '↳', 'route' => 'fleet.fuel-prices', 'routeParams' => ['action' => 'add'], 'permission' => 'fuel_prices.manage'],
                        ['key' => 'prices-list', 'label' => 'Price List', 'icon' => '↳', 'route' => 'fleet.fuel-prices', 'routeParams' => ['action' => 'list'], 'permission' => 'fuel_prices.view'],
                    ]
                ],
            ],
        ],
        [
            'title' => 'Business',
            'items' => [
                [
                    'key' => 'contracts',
                    'label' => 'Contracts',
                    'icon' => '📄',
                    'route' => 'fleet.contracts',
                    'permission' => 'contracts.view',
                    'children' => [
                        ['key' => 'contracts-add', 'label' => 'Add Contract', 'icon' => '↳', 'route' => 'fleet.contracts', 'routeParams' => ['action' => 'add'], 'permission' => 'contracts.manage'],
                        ['key' => 'contracts-list', 'label' => 'Contract List', 'icon' => '↳', 'route' => 'fleet.contracts', 'routeParams' => ['action' => 'list'], 'permission' => 'contracts.view'],
                    ]
                ],
                [
                    'key' => 'clients',
                    'label' => 'Client',
                    'icon' => '👥',
                    'route' => 'fleet.clients',
                    'permission' => 'clients.view',
                    'children' => [
                        ['key' => 'clients-add', 'label' => 'Add Client', 'icon' => '↳', 'route' => 'fleet.clients', 'routeParams' => ['action' => 'add'], 'permission' => 'clients.manage'],
                        ['key' => 'clients-list', 'label' => 'Client List', 'icon' => '↳', 'route' => 'fleet.clients', 'routeParams' => ['action' => 'list'], 'permission' => 'clients.view'],
                    ]
                ],
            ],
        ],
        [
            'title' => 'People & Partners',
            'items' => [
                [
                    'key' => 'drivers',
                    'label' => 'Drivers',
                    'icon' => '👨‍✈️',
                    'route' => 'fleet.drivers',
                    'permission' => 'drivers.view',
                    'children' => [
                        ['key' => 'drivers-add', 'label' => 'Add Driver', 'icon' => '↳', 'route' => 'fleet.drivers', 'routeParams' => ['action' => 'add'], 'permission' => 'drivers.manage'],
                        ['key' => 'drivers-list', 'label' => 'Driver List', 'icon' => '↳', 'route' => 'fleet.drivers', 'routeParams' => ['action' => 'list'], 'permission' => 'drivers.view'],
                    ]
                ],
                [
                    'key' => 'employees',
                    'label' => 'Employees',
                    'icon' => '👥',
                    'route' => 'fleet.employees',
                    'permission' => 'employees.view',
                    'children' => [
                        ['key' => 'employees-add', 'label' => 'Add Employee', 'icon' => '↳', 'route' => 'fleet.employees', 'routeParams' => ['action' => 'add'], 'permission' => 'employees.manage'],
                        ['key' => 'employees-list', 'label' => 'Employee List', 'icon' => '↳', 'route' => 'fleet.employees', 'routeParams' => ['action' => 'list'], 'permission' => 'employees.view'],
                    ]
                ],
                [
                    'key' => 'vendors',
                    'label' => 'Vendors',
                    'icon' => '🤝',
                    'route' => 'fleet.vendors',
                    'permission' => 'vendors.view',
                    'children' => [
                        ['key' => 'vendors-add', 'label' => 'Add Vendor', 'icon' => '↳', 'route' => 'fleet.vendors', 'routeParams' => ['action' => 'add'], 'permission' => 'vendors.manage'],
                        ['key' => 'vendors-list', 'label' => 'Vendor List', 'icon' => '↳', 'route' => 'fleet.vendors', 'routeParams' => ['action' => 'list'], 'permission' => 'vendors.view'],
                    ]
                ],
            ],
        ],
        [
            'title' => 'Finance & Reports',
            'items' => [
                ['key' => 'dues', 'label' => 'Dues & Payroll', 'icon' => '💵', 'route' => 'fleet.dues', 'permission' => 'dues.view'],
                [
                    'key' => 'reports',
                    'label' => 'Reports',
                    'icon' => '📊',
                    'route' => 'fleet.reports',
                    'permission' => 'reports.view',
                    'children' => [
                        ['key' => 'report-daily-driver-fuel', 'label' => 'Daily Report', 'icon' => '↳', 'route' => 'fleet.reports.daily-driver-fuel', 'permission' => 'reports.view'],
                        ['key' => 'report-weekly-driver-fuel', 'label' => 'Weekly Report', 'icon' => '↳', 'route' => 'fleet.reports.weekly-driver-fuel', 'permission' => 'reports.view'],
                        ['key' => 'report-monthly-driver-fuel', 'label' => 'Monthly Report', 'icon' => '↳', 'route' => 'fleet.reports.monthly-driver-fuel', 'permission' => 'reports.view'],
                    ]
                ],
            ],
        ],
        [
            'title' => 'System',
            'items' => [
                ['key' => 'users', 'label' => 'Users', 'icon' => '👤', 'route' => 'fleet.users', 'permission' => 'users.view'],
                ['key' => 'role-matrix', 'label' => 'Role Matrix', 'icon' => '🛡️', 'route' => 'fleet.role-matrix', 'permission' => 'role_matrix.view'],
                ['key' => 'release-tracker', 'label' => 'Release Tracker', 'icon' => '🚀', 'route' => 'fleet.release-tracker', 'super_admin_only' => true],
                [
                    'key' => 'master-data',
                    'label' => 'Master Data',
                    'icon' => '🗂️',
                    'route' => 'fleet.master-data',
                    'permission' => 'master_data.view',
                    'children' => [
                        ['key' => 'master-data-vehicle-categories', 'label' => 'Vehicle Category', 'icon' => '↳', 'route' => 'fleet.master-data.vehicle-categories', 'permission' => 'master_data.view'],
                        ['key' => 'master-data-vehicle-sub-categories', 'label' => 'Vehicle Sub Category', 'icon' => '↳', 'route' => 'fleet.master-data.vehicle-sub-categories', 'permission' => 'master_data.view'],
                        ['key' => 'master-data-party-types', 'label' => 'Party Type', 'icon' => '↳', 'route' => 'fleet.master-data.party-types', 'permission' => 'master_data.view'],
                        ['key' => 'master-data-document-names', 'label' => 'Document Type', 'icon' => '↳', 'route' => 'fleet.master-data.document-names', 'permission' => 'master_data.view'],
                        ['key' => 'master-data-licence-types', 'label' => 'Licence Type', 'icon' => '↳', 'route' => 'fleet.master-data.licence-types', 'permission' => 'master_data.view'],
                        ['key' => 'master-data-driver-contact-types', 'label' => 'Contact Type', 'icon' => '↳', 'route' => 'fleet.master-data.driver-contact-types', 'permission' => 'master_data.view'],
                        ['key' => 'master-data-client-types', 'label' => 'Client Type', 'icon' => '↳', 'route' => 'fleet.master-data.client-types', 'permission' => 'master_data.view'],
                        ['key' => 'master-data-contact-methods', 'label' => 'Contact Method', 'icon' => '↳', 'route' => 'fleet.master-data.contact-methods', 'permission' => 'master_data.view'],
                        ['key' => 'master-data-fuel-types', 'label' => 'Fuel Type', 'icon' => '↳', 'route' => 'fleet.master-data.fuel-types', 'permission' => 'master_data.view'],
                        ['key' => 'master-data-fuel-units', 'label' => 'Fuel Unit', 'icon' => '↳', 'route' => 'fleet.master-data.fuel-units', 'permission' => 'master_data.view'],
                        ['key' => 'master-data-payment-types', 'label' => 'Payment Types', 'icon' => '↳', 'route' => 'fleet.master-data.payment-types', 'permission' => 'master_data.view'],
                    ],
                ],
                ['key' => 'settings', 'label' => 'Settings', 'icon' => '⚙️', 'route' => 'fleet.settings', 'super_admin_only' => true],
            ],
        ],
    ],

    'options' => [
        'vendors' => ['ABC Transport Ltd.', 'Rahman Motors', 'Own Fleet'],
        'drivers' => ['Md. Karim', 'Shahidul Islam', 'Rafiq Ahmed'],
        'vehicle_categories' => [
            'Light-Duty Vehicle' => ['Pickup truck', 'Car / Sedan', 'Microbus'],
            'Medium-Duty Vehicle' => ['Van / Mini van', 'Box truck', 'Covered van'],
            'Heavy-Duty Vehicle' => ['Heavy bus / Coach', 'Prime mover', 'Heavy truck'],
            'Construction & Off-Road Machinery' => ['Excavator', 'Bulldozer', 'Loader'],
            'Two-Wheeler / Three-Wheeler' => ['Motorcycle', 'CNG Auto Rickshaw', 'Three-wheeler cargo'],
            'Electric & Alternative Fuel Vehicle' => ['Electric van', 'Hybrid car', 'CNG/LPG powered vehicle'],
        ],
        'usage_types' => [
            ['value' => 'Single shift', 'title' => 'Single shift', 'description' => 'One driver or one duty period per day'],
            ['value' => 'Double shift', 'title' => 'Double shift', 'description' => 'Two duty periods in a day'],
            ['value' => 'Spare drive', 'title' => 'Spare drive', 'description' => 'Backup vehicle when needed'],
        ],
        'fuel_types' => ['Diesel', 'Petrol/Octane', 'CNG', 'LPG', 'Electric', 'Hybrid Charge', 'Electric Charge', 'Other'],
        'fuel_price_types' => ['Diesel', 'Petrol/Octane', 'CNG', 'LPG', 'Hybrid Charge', 'Electric Charge', 'Other'],
        'fuel_units' => ['Per Liter', 'Taka', 'Per KG', 'Per kWh', 'Other'],
        'fuel_statuses' => ['Active', 'Inactive', 'Draft'],
        'document_templates' => ['Tax Token', 'Fitness Certificate', 'Route Permit'],
        'document_reminders' => ['30 days before', '15 days before', '7 days before'],
        'party_types' => ['Transport Vendor', 'Driver Supply Vendor', 'Fuel Station', 'Workshop / Garage', 'Spare Parts Supplier', 'Insurance Provider', 'General Supplier', 'Other'],
        'party_statuses' => ['Active', 'Inactive', 'Blacklisted', 'Draft'],
        'vendor_contractor_types' => ['Car Related', 'Non-Car Related'],
        'payment_terms' => ['Cash', 'Advance', '7 Days', '15 Days', '30 Days', 'Custom'],
        'payment_types' => ['Cash', 'Bank Transfer', 'Card', 'bKash', 'Nagad', 'Rocket', 'Cheque', 'Other'],
        'party_document_templates' => ['Trade License Copy', 'Vendor Agreement', 'NID Copy of Owner', 'Fuel Supply Agreement', 'Insurance Document'],
        'trip_statuses' => ['Initiated', 'Running', 'Completed', 'Archived', 'Other'],
        'trip_around' => ['Inside City', 'Outside City'],
        'trip_periods' => ['Within 24 Hours', 'Beyond 24 Hours'],
        'trip_purposes' => ['Client Visit', 'Staff Transport', 'Goods Delivery', 'Airport Drop', 'Official Duty'],

        'driver_license_types' => ['Lite', 'Medium', 'Heavy', 'Professional', 'Other'],
        'driver_salary_tenures' => ['Monthly', 'Weekly', 'Daily', 'Hourly', 'Contract', 'Other'],
        'driver_contact_types' => ['Personal', 'Home', 'Relative'],
        'driver_statuses' => ['Active', 'On Leave', 'Inactive', 'Blacklisted', 'Draft'],
        'driver_duty_types' => [
            ['value' => 'Single shift', 'title' => 'Single shift', 'description' => 'Regular daily driving duty'],
            ['value' => 'Spare driver', 'title' => 'Spare driver', 'description' => 'Used when main driver is absent'],
        ],
        'driver_document_templates' => ['NID Scan Copy', 'Driving License Copy', 'Police Verification', 'Medical Fitness Certificate', 'Appointment Letter', 'Training Certificate'],
        'client_types' => ['Corporate', 'Individual', 'Government', 'NGO', 'Other'],
        'client_statuses' => ['Active', 'Prospect', 'Inactive', 'Draft'],
        'client_contact_methods' => ['Phone', 'WhatsApp', 'Email', 'Any'],
        'attendance_statuses' => ['Initiated', 'Running', 'Completed'],
        'employee_statuses' => ['Active', 'On Leave', 'Inactive', 'Draft'],
        'employee_salary_tenures' => ['Monthly', 'Weekly', 'Daily', 'Hourly', 'Contract', 'Other'],
        'employee_designations' => ['Office Assistant', 'Accounts Assistant', 'Supervisor', 'Admin Officer', 'HR Officer', 'Other'],
        'employee_document_templates' => ['Employee NID Copy', 'Employee Appointment Letter', 'Educational Certificate', 'Experience Certificate', 'Police Verification Copy'],
    ],

    'trip_masters' => [
        'vehicles' => $tripVehicles,
        'drivers' => $tripDrivers,
        'vehicle_types' => $vehicleTypes,
        'driver_areas' => $driverAreas,
    ],


    'attendance_masters' => [
        'contracts' => [
            ['id' => 'CNT26060137', 'name' => 'BRAC Staff Transport', 'vehicles' => ['VHL26060137 - Toyota Noah', 'VHL26060138 - Nissan Caravan']],
            ['id' => 'CNT26060138', 'name' => 'City Office Support', 'vehicles' => ['VHL26060139 - Mitsubishi Pickup', 'VHL26060140 - Microbus']],
            ['id' => 'CNT26060139', 'name' => 'Airport Duty Service', 'vehicles' => ['VHL26060141 - Hiace', 'VHL26060142 - Premio']],
        ],
        'vehicle_driver_map' => [
            'VHL26060137 - Toyota Noah' => 'DVR26060137 - Kamal Hossain',
            'VHL26060138 - Nissan Caravan' => 'DVR26060138 - Jahangir Alam',
            'VHL26060139 - Mitsubishi Pickup' => 'DVR26060139 - Shafiqur Rahman',
            'VHL26060140 - Microbus' => 'DVR26060140 - Mizanur Rahman',
            'VHL26060141 - Hiace' => 'DVR26060141 - Rafiq Islam',
            'VHL26060142 - Premio' => 'DVR26060142 - Masud Rana',
        ],
        'drivers' => ['DVR26060137 - Kamal Hossain', 'DVR26060138 - Jahangir Alam', 'DVR26060139 - Shafiqur Rahman', 'DVR26060140 - Mizanur Rahman', 'DVR26060141 - Rafiq Islam', 'DVR26060142 - Masud Rana'],
        'yards' => ['Main Yard', 'Mirpur Yard', 'Airport Yard', 'Chattogram Yard'],
    ],

    'contracts' => [
        [
            'id' => 'metro',
            'label' => 'CN-2026-011 | Metro Logistics',
            'vehicles' => [
                ['id' => 'dm-sa-0117', 'name' => 'Dhaka Metro-SA-12-0117', 'primary' => 'Diesel', 'primaryRate' => 109, 'secondary' => 'CNG', 'secondaryRate' => 45, 'secondaryAvailable' => true],
                ['id' => 'dm-ta-3364', 'name' => 'Dhaka Metro-TA-19-3364', 'primary' => 'Octane', 'primaryRate' => 125, 'secondary' => '', 'secondaryRate' => 0, 'secondaryAvailable' => false],
            ],
        ],
        [
            'id' => 'city',
            'label' => 'CN-2026-017 | City Transport Support',
            'vehicles' => [
                ['id' => 'dm-ga-2210', 'name' => 'Dhaka Metro-GA-22-2210', 'primary' => 'Petrol', 'primaryRate' => 122, 'secondary' => 'LPG', 'secondaryRate' => 68, 'secondaryAvailable' => true],
                ['id' => 'dm-kha-4302', 'name' => 'Dhaka Metro-KHA-18-4302', 'primary' => 'Diesel', 'primaryRate' => 109, 'secondary' => '', 'secondaryRate' => 0, 'secondaryAvailable' => false],
            ],
        ],
        [
            'id' => 'north',
            'label' => 'CN-2026-021 | North Zone Delivery',
            'vehicles' => [
                ['id' => 'dh-na-1456', 'name' => 'Dhaka Metro-NA-14-1456', 'primary' => 'Diesel', 'primaryRate' => 109, 'secondary' => 'CNG', 'secondaryRate' => 45, 'secondaryAvailable' => true],
            ],
        ],
    ],

    'photo_requirements' => [
        ['key' => 'vehicle', 'title' => '1. Vehicle Photo', 'description' => 'Take a photo of the vehicle.', 'icon' => '🚗', 'required' => true],
        ['key' => 'fuel', 'title' => '2. Fuel / Dispenser Photo', 'description' => 'Take a photo of the fuel dispenser or fueling.', 'icon' => '⛽', 'required' => true],
        ['key' => 'odo', 'title' => '3. ODO Meter Photo', 'description' => 'Take a clear photo of the meter reading.', 'icon' => '📟', 'required' => true],
        ['key' => 'other', 'title' => '4. Other Photo', 'description' => 'Optional. Use only if extra proof is needed.', 'icon' => '📎', 'required' => false],
    ],

    'samples' => [
        'vehicles' => [
            [
                'id' => 'VHL26060137',
                'name' => 'Dhaka Pickup 01',
                'regNo' => 'DHAKA-METRO-TA-11-2345',
                'vendor' => 'ABC Transport Ltd.',
                'model' => 'Toyota Hilux 2021',
                'color' => 'White',
                'engineNo' => 'ENG-78219',
                'mileage' => '8.5',
                'odo' => '45230',
                'category' => 'Light-Duty Vehicle',
                'subCategory' => 'Pickup truck',
                'usage' => 'Single shift',
                'driver' => 'Md. Karim',
                'rent' => '35000',
                'fuels' => [
                    ['type' => 'Diesel', 'priority' => 'Primary', 'rate' => '110'],
                    ['type' => 'CNG', 'priority' => 'Secondary', 'rate' => '55'],
                ],
                'docs' => [
                    ['name' => 'Tax Token', 'expiry' => '2026-09-20', 'reminder' => '30 days before'],
                    ['name' => 'Fitness Certificate', 'expiry' => '2026-07-10', 'reminder' => '30 days before'],
                    ['name' => 'Route Permit', 'expiry' => '2026-12-15', 'reminder' => '30 days before'],
                ],
                'status' => 'Active',
                'notes' => 'Sample data for client review.',
            ],
            [
                'id' => 'VHL26060212',
                'name' => 'Airport Van 02',
                'regNo' => 'DHAKA-METRO-CHA-22-9876',
                'vendor' => 'Own Fleet',
                'model' => 'Toyota Hiace 2020',
                'color' => 'Silver',
                'engineNo' => 'ENG-55710',
                'mileage' => '7.2',
                'odo' => '78200',
                'category' => 'Medium-Duty Vehicle',
                'subCategory' => 'Van / Mini van',
                'usage' => 'Double shift',
                'driver' => 'Shahidul Islam',
                'rent' => '0',
                'fuels' => [
                    ['type' => 'Petrol/Octane', 'priority' => 'Primary', 'rate' => '125'],
                    ['type' => 'LPG', 'priority' => 'Secondary', 'rate' => '70'],
                ],
                'docs' => [
                    ['name' => 'Fitness Certificate', 'expiry' => '2026-06-25', 'reminder' => '30 days before'],
                ],
                'status' => 'Needs document review',
                'notes' => '',
            ],
        ],
        'fuel_prices' => [
            ['fuelPriceId' => 'FPR26060137', 'fuelType' => 'Diesel', 'name' => 'Diesel - Standard Rate', 'price' => '122', 'unit' => 'Per Liter', 'effectiveDate' => '2026-06-01', 'reference' => 'Govt. circular June 2026', 'status' => 'Active', 'remarks' => 'Primary diesel rate for regular fleet fuel recharge.'],
            ['fuelPriceId' => 'FPR26060138', 'fuelType' => 'Petrol/Octane', 'name' => 'Octane - Standard Rate', 'price' => '130', 'unit' => 'Per Liter', 'effectiveDate' => '2026-06-01', 'reference' => 'Market update', 'status' => 'Active', 'remarks' => 'Used for car and light vehicle recharge.'],
            ['fuelPriceId' => 'FPR26060139', 'fuelType' => 'CNG', 'name' => 'CNG - Corporate Rate', 'price' => '48', 'unit' => 'Per KG', 'effectiveDate' => '2026-05-15', 'reference' => 'Management approval', 'status' => 'Inactive', 'remarks' => 'Older rate kept for reference.'],
        ],
        'fuel_recharges' => $fuelRechargeSamples,
        'parties' => [
            [
                'partyId' => 'VND26060137',
                'partyName' => 'Speed Transport Services',
                'partyType' => 'Transport Vendor',
                'vendorContractorType' => 'Car Related',
                'status' => 'Active',
                'phone' => '01711000001',
                'email' => 'ops@speedtransport.com',
                'whatsapp' => '01711000001',
                'tradeLicense' => 'TRD-2026-8872',
                'tinBin' => 'TIN-778899',
                'paymentTerms' => '30 Days',
                'address' => 'Tejgaon Industrial Area, Dhaka',
                'about' => 'Provides backup vehicles and drivers for corporate contracts.',
                'contacts' => [
                    ['name' => 'Kamrul Hasan', 'role' => 'Operations Manager', 'phone' => '01819000011', 'email' => 'kamrul@speedtransport.com', 'whatsapp' => '01819000011'],
                ],
                'documents' => [
                    ['name' => 'Trade License Copy', 'number' => 'TRD-2026-8872', 'expiry' => '2027-06-30'],
                    ['name' => 'Vendor Agreement', 'number' => 'VA-2026-01', 'expiry' => '2026-12-31'],
                ],
            ],
            [
                'partyId' => 'VND26060138',
                'partyName' => 'Rahman Driver Supply',
                'partyType' => 'Driver Supply Vendor',
                'vendorContractorType' => 'Car Related',
                'status' => 'Active',
                'phone' => '01922000002',
                'email' => '',
                'whatsapp' => '01922000002',
                'tradeLicense' => '',
                'tinBin' => '',
                'paymentTerms' => '15 Days',
                'address' => 'Gazipur Chowrasta, Gazipur',
                'about' => 'Supplies spare drivers for urgent duty.',
                'contacts' => [
                    ['name' => 'Md. Rashed', 'role' => 'Owner', 'phone' => '01788000021', 'email' => '', 'whatsapp' => '01788000021'],
                ],
                'documents' => [
                    ['name' => 'NID Copy of Owner', 'number' => 'NID-1988XXXX', 'expiry' => ''],
                ],
            ],
            [
                'partyId' => 'VND26060139',
                'partyName' => 'Green Pump Fuel Station',
                'partyType' => 'Fuel Station',
                'vendorContractorType' => 'Non-Car Related',
                'status' => 'Inactive',
                'phone' => '01633000003',
                'email' => 'fuel@greenpump.com',
                'whatsapp' => '',
                'tradeLicense' => 'TRD-FUEL-7721',
                'tinBin' => 'BIN-88001',
                'paymentTerms' => 'Cash',
                'address' => 'Airport Road, Dhaka',
                'about' => 'Fuel supplier. Currently inactive due to price dispute.',
                'contacts' => [
                    ['name' => 'Farhana Sultana', 'role' => 'Billing', 'phone' => '01877000031', 'email' => 'farhana@greenpump.com', 'whatsapp' => ''],
                ],
                'documents' => [
                    ['name' => 'Fuel Supply Agreement', 'number' => 'FSA-55', 'expiry' => '2026-08-31'],
                ],
            ],
        ],

        'drivers' => [
            [
                'driverId' => 'DVR26060137', 'fullName' => 'Md. Karim Hossain', 'fatherName' => 'Md. Abdul Mannan', 'motherName' => 'Mst. Amena Begum', 'contact' => '01712000000', 'secondaryContact' => '01812000000', 'whatsapp' => '01712000000', 'email' => 'karim.driver@example.com', 'dob' => '1988-04-12', 'age' => '38', 'nid' => '19881234567890123', 'reference' => 'Ashish', 'licenseNo' => 'DL-DHK-443219', 'licenseType' => 'Heavy', 'licenseValidity' => '2030-12-31', 'salary' => '28000', 'salaryTenure' => 'Monthly', 'otRate' => '70', 'workingHour' => '270', 'vendor' => 'Own Payroll', 'status' => 'Active', 'duty' => 'Single shift', 'presentAddress' => 'Mirpur 11, Dhaka', 'permanentAddress' => 'Shibpur, Narsingdi', 'about' => 'Experienced bus and pickup driver.', 'documents' => [ ['name' => 'NID Scan Copy', 'number' => 'NID-19881234567890123', 'expiry' => ''], ['name' => 'Driving License Copy', 'number' => 'DL-DHK-443219', 'expiry' => '2030-12-31'], ['name' => 'Medical Fitness Certificate', 'number' => 'MF-2026-014', 'expiry' => '2027-06-30'] ],
            ],
            [
                'driverId' => 'DVR26060138', 'fullName' => 'Shahidul Islam', 'fatherName' => 'Abdul Jalil', 'motherName' => 'Saleha Begum', 'contact' => '01933000000', 'secondaryContact' => '', 'whatsapp' => '01933000000', 'email' => '', 'dob' => '1991-01-20', 'age' => '35', 'nid' => '19911234567890123', 'reference' => 'Rahman Driver Supply', 'licenseNo' => 'DL-CTG-12902', 'licenseType' => 'Medium', 'licenseValidity' => '2026-08-15', 'salary' => '1500', 'salaryTenure' => 'Daily', 'otRate' => '60', 'workingHour' => '260', 'vendor' => 'Rahman Driver Supply', 'status' => 'Active', 'duty' => 'Spare driver', 'presentAddress' => 'Gazipur Sadar, Gazipur', 'permanentAddress' => 'Satkania, Chattogram', 'about' => 'Mostly assigned for spare duty.', 'documents' => [ ['name' => 'Driving License Copy', 'number' => 'DL-CTG-12902', 'expiry' => '2026-08-15'], ['name' => 'Police Verification', 'number' => 'PV-7741', 'expiry' => '2028-01-01'] ],
            ],
            [
                'driverId' => 'DVR26060139', 'fullName' => 'Rafiq Ahmed', 'fatherName' => 'Late Harun Ahmed', 'motherName' => 'Rokeya Begum', 'contact' => '01644000000', 'secondaryContact' => '', 'whatsapp' => '', 'email' => 'rafiq@example.com', 'dob' => '1984-11-09', 'age' => '42', 'nid' => '19841234567890123', 'reference' => 'Vendor', 'licenseNo' => 'DL-SYL-55210', 'licenseType' => 'Lite', 'licenseValidity' => '2025-12-20', 'salary' => '24000', 'salaryTenure' => 'Monthly', 'otRate' => '50', 'workingHour' => '270', 'vendor' => 'ABC Transport Ltd.', 'status' => 'On Leave', 'duty' => 'Double shift', 'presentAddress' => 'Uttara, Dhaka', 'permanentAddress' => 'Beanibazar, Sylhet', 'about' => 'On leave for family reason.', 'documents' => [ ['name' => 'NID Scan Copy', 'number' => 'NID-19841234567890123', 'expiry' => ''], ['name' => 'Appointment Letter', 'number' => 'AL-2025-88', 'expiry' => ''] ],
            ],
        ],
        'clients' => [
            [
                'clientId' => 'CLI26060137', 'clientName' => 'ABC Logistics Ltd.', 'email' => 'ops@abclogistics.com', 'phone' => '01711000001', 'whatsapp' => '01711000001', 'reference' => 'Ashiq from Sales', 'clientType' => 'Corporate', 'status' => 'Active', 'contactMethod' => 'Phone', 'address' => 'Tejgaon Industrial Area, Dhaka', 'about' => 'Large logistics and yard operations client. Requires monthly fleet support and quick response.', 'contacts' => [ ['name' => 'Kamrul Hasan', 'role' => 'Operations Manager', 'phone' => '01819000011', 'whatsapp' => '01819000011', 'email' => 'kamrul@abclogistics.com'], ['name' => 'Nusrat Jahan', 'role' => 'Accounts', 'phone' => '01819000012', 'whatsapp' => '', 'email' => 'accounts@abclogistics.com'] ],
            ],
            [
                'clientId' => 'CLI26060138', 'clientName' => 'Rahman Builders', 'email' => '', 'phone' => '01922000002', 'whatsapp' => '01922000002', 'reference' => 'Vendor referral', 'clientType' => 'Corporate', 'status' => 'Prospect', 'contactMethod' => 'WhatsApp', 'address' => 'Gazipur Chowrasta, Gazipur', 'about' => 'Prospective construction client with 8+ heavy vehicles.', 'contacts' => [ ['name' => 'Md. Rashed', 'role' => 'Site Admin', 'phone' => '01788000021', 'whatsapp' => '01788000021', 'email' => ''] ],
            ],
            [
                'clientId' => 'CLI26060139', 'clientName' => 'Green Field NGO', 'email' => 'fleet@greenfield.org', 'phone' => '01633000003', 'whatsapp' => '', 'reference' => 'Website inquiry', 'clientType' => 'NGO', 'status' => 'Active', 'contactMethod' => 'Email', 'address' => 'Banani, Dhaka', 'about' => 'NGO client managing field vehicles across districts.', 'contacts' => [ ['name' => 'Farhana Sultana', 'role' => 'Procurement', 'phone' => '01877000031', 'whatsapp' => '', 'email' => 'farhana@greenfield.org'], ['name' => 'Imran Hossain', 'role' => 'Field Coordinator', 'phone' => '01877000032', 'whatsapp' => '01877000032', 'email' => ''] ],
            ],
        ],
        'employees' => [
            [
                'employeeId' => 'EMP26060137',
                'fullName' => 'Md. Rafiq Islam',
                'fatherName' => 'Abdul Karim',
                'motherName' => 'Rokeya Begum',
                'nid' => '1987654321001',
                'contactNumber' => '01711000001',
                'email' => 'rafiq@fleetman.com',
                'reference' => 'Operations Team',
                'designation' => 'Office Assistant',
                'joiningDate' => '2026-06-01',
                'status' => 'Active',
                'socialMedia' => '',
                'age' => '28',
                'salary' => '18000',
                'salaryTenure' => 'Monthly',
                'overtimeRate' => '60',
                'presentAddress' => 'Mirpur, Dhaka',
                'permanentAddress' => 'Kishoreganj, Dhaka',
                'about' => 'Supports daily office and fleet operations.',
                'photoName' => 'employee-rafiq.jpg',
            ],
            [
                'employeeId' => 'EMP26060138',
                'fullName' => 'Shila Akter',
                'fatherName' => 'Mohammad Ali',
                'motherName' => 'Hasina Begum',
                'nid' => '1994567890123',
                'contactNumber' => '01822000002',
                'email' => 'shila@fleetman.com',
                'reference' => 'HR Referral',
                'designation' => 'Accounts Assistant',
                'joiningDate' => '2026-05-15',
                'status' => 'Active',
                'socialMedia' => '',
                'age' => '30',
                'salary' => '22000',
                'salaryTenure' => 'Monthly',
                'overtimeRate' => '0',
                'presentAddress' => 'Uttara, Dhaka',
                'permanentAddress' => 'Comilla',
                'about' => 'Handles basic voucher and accounting support.',
                'photoName' => 'employee-shila.jpg',
            ],
            [
                'employeeId' => 'EMP26060139',
                'fullName' => 'Saiful Islam',
                'fatherName' => 'Nurul Islam',
                'motherName' => 'Shirin Akter',
                'nid' => '1976543210987',
                'contactNumber' => '01933000003',
                'email' => '',
                'reference' => 'Supervisor recommendation',
                'designation' => 'Supervisor',
                'joiningDate' => '2026-04-20',
                'status' => 'On Leave',
                'socialMedia' => '',
                'age' => '35',
                'salary' => '26000',
                'salaryTenure' => 'Monthly',
                'overtimeRate' => '75',
                'presentAddress' => 'Tongi, Gazipur',
                'permanentAddress' => 'Mymensingh',
                'about' => 'Field supervision for drivers and trips.',
                'photoName' => 'employee-saiful.jpg',
            ],
        ],

        'driver_attendance' => [
            ['logId' => 'DL26060137', 'date' => '2026-06-02', 'contract' => 'CNT26060137 - BRAC Staff Transport', 'vehicle' => 'VHL26060137 - Toyota Noah', 'driver' => 'DVR26060137 - Kamal Hossain', 'yard' => 'Main Yard', 'startTime' => '09:00', 'endTime' => '17:00', 'status' => 'Completed', 'kmStart' => '12540', 'kmEnd' => '12605', 'distance' => '65', 'hours' => '8h 0m', 'notes' => 'Regular office movement and staff pickup.'],
            ['logId' => 'DL26060138', 'date' => '2026-06-02', 'contract' => 'CNT26060138 - City Office Support', 'vehicle' => 'VHL26060139 - Mitsubishi Pickup', 'driver' => 'DVR26060139 - Shafiqur Rahman', 'yard' => 'Mirpur Yard', 'startTime' => '08:30', 'endTime' => '', 'status' => 'Running', 'kmStart' => '8740', 'kmEnd' => '', 'distance' => '0', 'hours' => '0h 0m', 'notes' => 'Field support duty still ongoing.'],
            ['logId' => 'DL26060139', 'date' => '2026-06-01', 'contract' => 'CNT26060139 - Airport Duty Service', 'vehicle' => 'VHL26060141 - Hiace', 'driver' => 'DVR26060141 - Rafiq Islam', 'yard' => 'Airport Yard', 'startTime' => '06:15', 'endTime' => '13:45', 'status' => 'Completed', 'kmStart' => '22150', 'kmEnd' => '22205', 'distance' => '55', 'hours' => '7h 30m', 'notes' => 'Airport staff drop and pickup.'],
        ],
        'trips' => [
            ['tripId' => 'TRP26060137', 'startDate' => '2026-06-02', 'vehicle' => 'VHL26060137 - Dhaka Pickup 01', 'vehicleId' => 'VHL26060137', 'driver' => 'DVR26060137 - Md. Karim Hossain', 'driverId' => 'DVR26060137', 'purpose' => 'Client Visit', 'fromLocation' => 'Head Office', 'toLocation' => 'Banani Client Office', 'odoStart' => '12540', 'odoEnd' => '12578', 'totalCost' => '1250.00', 'payments' => [['method' => 'Cash', 'amount' => '500.00', 'reference' => ''], ['method' => 'bKash', 'amount' => '250.00', 'reference' => 'BK-260602-001']], 'paidAmount' => '750.00', 'balanceDue' => '500.00', 'paymentState' => 'Partially Paid', 'details' => 'Vehicle assigned for client visit and return within the same day.'],
            ['tripId' => 'TRP26060138', 'startDate' => '2026-06-01', 'vehicle' => 'VHL26060212 - Airport Van 02', 'vehicleId' => 'VHL26060212', 'driver' => 'DVR26060138 - Shahidul Islam', 'driverId' => 'DVR26060138', 'purpose' => 'Staff Transport', 'fromLocation' => 'Dhaka', 'toLocation' => 'Chattogram', 'odoStart' => '', 'odoEnd' => '', 'totalCost' => '7450.00', 'payments' => [], 'paidAmount' => '0.00', 'balanceDue' => '7450.00', 'paymentState' => 'Unpaid', 'details' => 'Vehicle assigned for staff transport to Chattogram.'],
            ['tripId' => 'TRP26060139', 'startDate' => '2026-05-28', 'vehicle' => 'VHL26060137 - Dhaka Pickup 01', 'vehicleId' => 'VHL26060137', 'driver' => 'DVR26060139 - Rafiq Ahmed', 'driverId' => 'DVR26060139', 'purpose' => 'Goods Delivery', 'fromLocation' => 'Warehouse', 'toLocation' => 'Gulshan', 'odoStart' => '8730', 'odoEnd' => '8795', 'totalCost' => '800.00', 'payments' => [['method' => 'Bank Transfer', 'amount' => '800.00', 'reference' => 'TRX-260528-093']], 'paidAmount' => '800.00', 'balanceDue' => '0.00', 'paymentState' => 'Paid', 'details' => 'Goods delivered successfully.'],
        ],
    ],
];

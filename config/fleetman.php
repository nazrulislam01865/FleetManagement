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

return [
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
                ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => '🏠', 'route' => 'fleet.vehicles'],
                ['key' => 'trips', 'label' => 'Trips', 'icon' => '🧭', 'route' => 'fleet.trips'],
                ['key' => 'drive-log', 'label' => 'Drive log', 'icon' => '📝', 'route' => null],
                ['key' => 'add-drive-log', 'label' => 'Add Drive Log', 'icon' => '➕', 'route' => null],
                ['key' => 'yards', 'label' => 'Yards', 'icon' => '🅿️', 'route' => null],
            ],
        ],
        [
            'title' => 'Fleet Management',
            'items' => [
                ['key' => 'vehicles', 'label' => 'Vehicles', 'icon' => '🚗', 'route' => 'fleet.vehicles'],
                ['key' => 'fuel-recharge', 'label' => 'Fuel Recharge', 'icon' => '⛽', 'route' => 'fleet.fuel-recharge'],
                ['key' => 'fuel-prices', 'label' => 'Fuel Prices', 'icon' => '⛽', 'route' => 'fleet.fuel-prices'],
                ['key' => 'documents', 'label' => 'Documents', 'icon' => '🧾', 'route' => null],
                ['key' => 'maintenance', 'label' => 'Maintenance', 'icon' => '🔧', 'route' => null],
            ],
        ],
        [
            'title' => 'Business',
            'items' => [
                ['key' => 'contracts', 'label' => 'Contracts', 'icon' => '📄', 'route' => null],
                ['key' => 'clients', 'label' => 'Clients', 'icon' => '👥', 'route' => null],
            ],
        ],
        [
            'title' => 'People & Partners',
            'items' => [
                ['key' => 'drivers', 'label' => 'Drivers', 'icon' => '👨‍✈️', 'route' => null],
                ['key' => 'vendors', 'label' => 'Vendors & Parties', 'icon' => '🤝', 'route' => 'fleet.vendors'],
            ],
        ],
        [
            'title' => 'Finance & Reports',
            'items' => [
                ['key' => 'reports', 'label' => 'Reports', 'icon' => '📊', 'route' => null],
            ],
        ],
        [
            'title' => 'System',
            'items' => [
                ['key' => 'settings', 'label' => 'Settings', 'icon' => '⚙️', 'route' => null],
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
        'fuel_units' => ['Per Liter', 'Per KG', 'Per kWh', 'Other'],
        'fuel_statuses' => ['Active', 'Inactive', 'Draft'],
        'document_templates' => ['Tax Token', 'Fitness Certificate', 'Route Permit'],
        'document_reminders' => ['30 days before', '15 days before', '7 days before'],
        'party_types' => ['Transport Vendor', 'Driver Supply Vendor', 'Fuel Station', 'Workshop / Garage', 'Spare Parts Supplier', 'Insurance Provider', 'General Supplier', 'Other'],
        'party_statuses' => ['Active', 'Inactive', 'Blacklisted', 'Draft'],
        'payment_terms' => ['Cash', 'Advance', '7 Days', '15 Days', '30 Days', 'Custom'],
        'party_document_templates' => ['Trade License Copy', 'Vendor Agreement', 'NID Copy of Owner', 'Fuel Supply Agreement', 'Insurance Document'],
        'trip_statuses' => ['Initiated', 'Running', 'Completed', 'Archived', 'Other'],
        'trip_around' => ['Inside City', 'Outside City'],
        'trip_periods' => ['Within 24 Hours', 'Beyond 24 Hours'],
        'trip_purposes' => ['Client Visit', 'Staff Transport', 'Goods Delivery', 'Airport Drop', 'Official Duty'],
    ],

    'trip_masters' => [
        'vehicles' => $tripVehicles,
        'drivers' => $tripDrivers,
        'vehicle_types' => $vehicleTypes,
        'driver_areas' => $driverAreas,
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
        'parties' => [
            [
                'partyId' => 'VND26060137',
                'partyName' => 'Speed Transport Services',
                'partyType' => 'Transport Vendor',
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
        'trips' => [
            ['tripId' => 'TRP26060137', 'startDate' => '2026-06-02', 'endDate' => '2026-06-02', 'vehicle' => 'VHL2606100 - Toyota Noah 1', 'driver' => 'DVR2606100 - Kamal Hossain', 'status' => 'Initiated', 'tripAround' => 'Inside City', 'tripPeriod' => 'Within 24 Hours', 'purpose' => 'Client Visit', 'fromLocation' => 'Head Office', 'toLocation' => 'Banani Client Office', 'odoStart' => '12540', 'odoEnd' => '12578', 'fuelCost' => '900', 'foodCost' => '250', 'tolls' => '0', 'otherCost' => '100', 'accommodationCost' => '0', 'totalCost' => '1250', 'details' => 'Vehicle assigned for client visit and return within same day.'],
            ['tripId' => 'TRP26060138', 'startDate' => '2026-06-01', 'endDate' => '2026-06-03', 'vehicle' => 'VHL2606101 - Nissan Caravan 2', 'driver' => 'DVR2606101 - Jahangir Alam', 'status' => 'Running', 'tripAround' => 'Outside City', 'tripPeriod' => 'Beyond 24 Hours', 'purpose' => 'Staff Transport', 'fromLocation' => 'Dhaka', 'toLocation' => 'Chattogram', 'odoStart' => '22410', 'odoEnd' => '', 'fuelCost' => '4200', 'foodCost' => '900', 'tolls' => '500', 'otherCost' => '350', 'accommodationCost' => '1500', 'totalCost' => '7450', 'details' => 'Vehicle sent for staff transport to Chattogram. Trip still running.'],
            ['tripId' => 'TRP26060139', 'startDate' => '2026-05-28', 'endDate' => '2026-05-28', 'vehicle' => 'VHL2606102 - Mitsubishi Pickup 3', 'driver' => 'DVR2606102 - Shafiqur Rahman', 'status' => 'Completed', 'tripAround' => 'Inside City', 'tripPeriod' => 'Within 24 Hours', 'purpose' => 'Goods Delivery', 'fromLocation' => 'Warehouse', 'toLocation' => 'Gulshan', 'odoStart' => '8730', 'odoEnd' => '8795', 'fuelCost' => '650', 'foodCost' => '150', 'tolls' => '0', 'otherCost' => '0', 'accommodationCost' => '0', 'totalCost' => '800', 'details' => 'Goods delivered successfully and trip completed.'],
        ],
    ],
];

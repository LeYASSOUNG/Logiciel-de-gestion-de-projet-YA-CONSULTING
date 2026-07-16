
$u = App\Models\User::firstOrCreate(
    ['email' => 'diarrassoubayassoungo33@gmail.com'],
    ['name' => 'Diarrassouba', 'password' => Hash::make('150703')]
);
$u->assignRole('admin');
echo 'OK';


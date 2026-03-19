<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@pnc.com',
                'password' => Hash::make('admin123'),
                'role_id' => 1, // Admin role
                'is_active' => true,
                'calendar_id' => null,
            ],
            [
                'name' => 'Davy',
                'email' => 'davy@pnc.com',
                'password' => Hash::make('davy123'),
                'role_id' => 2, // Teacher role
                'is_active' => true,
                'profile_image' => '/teacherFaces/davy.jpg',
                'calendar_id' => 'passerellesnumeriques.org_353233363037333530@resource.calendar.google.com',
            ],
            [
                'name' => 'Him',
                'email' => 'him@pnc.com',
                'password' => Hash::make('him123'),
                'role_id' => 2, // Teacher role
                'is_active' => true,
                'profile_image' => '/teacherFaces/him.jpg',
                'calendar_id' => 'passerellesnumeriques.org_343437393530363136@resource.calendar.google.com',
            ],
            [
                'name' => 'Lavy',
                'email' => 'lavy@pnc.com',
                'password' => Hash::make('lavy123'),
                'role_id' => 2, // Teacher role
                'is_active' => true,
                'profile_image' => '/teacherFaces/lavy.jpg',
                'calendar_id' => 'passerellesnumeriques.org_2d3331373838323735363330@resource.calendar.google.com',
            ],
            [
                'name' => 'Mengheang',
                'email' => 'mengheang@pnc.com',
                'password' => Hash::make('mengheang123'),
                'role_id' => 2, // Teacher role
                'is_active' => true,
                'profile_image' => '/teacherFaces/mengheang.jpg',
                'calendar_id' => 'c_1886h9lqonri4ig0noe2vrfvp8fb8@resource.calendar.google.com',
            ],
            [
                'name' => 'Ouchi',
                'email' => 'ouchi@pnc.com',
                'password' => Hash::make('ouchi123'),
                'role_id' => 2, // Teacher role
                'is_active' => true,
                'profile_image' => '/teacherFaces/ouchi.jpg',
                'calendar_id' => 'c_188b20cg9s5uoh12jobk987cfbh2g@resource.calendar.google.com',
            ],
            [
                'name' => 'Puthy',
                'email' => 'puthy@pnc.com',
                'password' => Hash::make('puthy123'),
                'role_id' => 2, // Teacher role
                'is_active' => true,
                'profile_image' => '/teacherFaces/puthy.jpg',
                'calendar_id' => 'passerellesnumeriques.org_3733323437383733383932@resource.calendar.google.com',
            ],
            [
                'name' => 'Rady',
                'email' => 'rady@pnc.com',
                'password' => Hash::make('rady123'),
                'role_id' => 2, // Teacher role
                'is_active' => true,
                'profile_image' => '/teacherFaces/rady.png',
                'calendar_id' => 'passerellesnumeriques.org_2d3132393337373934393735@resource.calendar.google.com',
            ],
            [
                'name' => 'Savoeurn',
                'email' => 'savoeurn@pnc.com',
                'password' => Hash::make('savoeurn123'),
                'role_id' => 2, // Teacher role
                'is_active' => true,
                'profile_image' => '/teacherFaces/savouern.jpg',
                'calendar_id' => 'passerellesnumeriques.org_3539373731343733353932@resource.calendar.google.com',
            ],
            [
                'name' => 'Sim',
                'email' => 'sim@pnc.com',
                'password' => Hash::make('sim123'),
                'role_id' => 2, // Teacher role
                'is_active' => true,
                'profile_image' => '/teacherFaces/sim.jpg',
                'calendar_id' => 'passerellesnumeriques.org_3635313135323433383533@resource.calendar.google.com',
            ],
            [
                'name' => 'Sokhom',
                'email' => 'sokhom@pnc.com',
                'password' => Hash::make('sokhom123'),
                'role_id' => 2, // Teacher role
                'is_active' => true,
                'profile_image' => '/teacherFaces/sokhom.jpg',
                'calendar_id' => 'passerellesnumeriques.org_2d3633393338303431343434@resource.calendar.google.com',
            ],
            [
                'name' => 'Somkhan',
                'email' => 'somkhan@pnc.com',
                'password' => Hash::make('somkhan123'),
                'role_id' => 2, // Teacher role
                'is_active' => true,
                'profile_image' => '/teacherFaces/somkhan.jpg',
                'calendar_id' => 'passerellesnumeriques.org_3933333731393139373031@resource.calendar.google.com',
            ],
            [
                'name' => 'Sovanchansreyleap',
                'email' => 'sovanchansreyleap@pnc.com',
                'password' => Hash::make('sovanchansreyleap123'),
                'role_id' => 2, // Teacher role
                'is_active' => true,
                'profile_image' => '/teacherFaces/sovanchansreyleap.jpg',
                'calendar_id' => 'c_1884lpdesdih0irbl36ss1j7vt7aq@resource.calendar.google.com',
            ],
            [
                'name' => 'Vandy',
                'email' => 'vandy@pnc.com',
                'password' => Hash::make('vandy123'),
                'role_id' => 2, // Teacher role
                'is_active' => true,
                'profile_image' => '/teacherFaces/sokhom.jpg',
                'calendar_id' => 'passerellesnumeriques.org_3331363536313232373638@resource.calendar.google.com',
            ],
            [
                'name' => 'Yon',
                'email' => 'yon@pnc.com',
                'password' => Hash::make('yon123'),
                'role_id' => 2, // Teacher role
                'is_active' => true,
                'calendar_id' => 'c_1882ckecmfgb0h7fmha0u9t3rbd5g@resource.calendar.google.com',
            ],
            [
                'name' => 'Education Team User',
                'email' => 'education@pnc.com',
                'password' => Hash::make('education123'),
                'role_id' => 3, // Education Team
                'is_active' => true,
                'calendar_id' => null,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                $user
            );
        }
    }
}

<?php
use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentTableSeeder extends Seeder
{

    public function run()
    {
        Department::truncate();
        $departments = ['Sales & Marketing', 'Operations', 'Management'];

        foreach ($departments as $key => $value) {
            Department::create([
                'name' => $value
            ]);
        }
    }
}

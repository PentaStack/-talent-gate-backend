<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Technology;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoryTechnologySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Software Engineering', 'Artificial Intelligence', 'Data Science',
            'DevOps & Infrastructure', 'Product Design', 'Product Management',
            'Quality Assurance', 'Engineering Management',
        ];

        foreach ($categories as $name) {
            Category::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }

        $technologies = [
            'JavaScript', 'TypeScript', 'Python', 'Rust', 'Go', 'Java', 'C++', 'C#', 'PHP', 'Ruby',
            'React', 'Vue.js', 'Angular', 'Node.js', 'Laravel', 'Django', 'FastAPI', 'Spring Boot',
            'PostgreSQL', 'MySQL', 'MongoDB', 'Redis', 'Docker', 'Kubernetes', 'AWS', 'GCP',
            'PyTorch', 'TensorFlow', 'GraphQL', 'gRPC',
        ];

        foreach ($technologies as $name) {
            Technology::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
    }
}

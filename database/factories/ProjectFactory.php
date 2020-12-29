<?php

namespace Database\Factories;

use App\Models\Ns;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProjectFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'ns_id'      => Ns::factory(),
            'project_id' => $this->faker->numberBetween(1, 999999),
            'name'       => $this->faker->word,
            'env'        => null,
            'creator'    => $this->faker->userName,
            'branch'     => $this->faker->word,
            'commit'     => $this->faker->word,
        ];
    }
}

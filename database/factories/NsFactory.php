<?php

namespace Database\Factories;

use App\Models\Ns;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Ns::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name'      => $this->faker->word,
            'user_id'   => $this->faker->numberBetween(1, 100),
            'user_name' => $this->faker->name,
        ];
    }
}

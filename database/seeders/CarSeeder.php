<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Car;

class CarSeeder extends Seeder
{
    public function run()
    {
        $cars = [
            ['brand' => 'Toyota', 'model' => 'Noah', 'numberPlate' => 'UAA 001A', 'dailyRate' => 150000, 'seats' => 8, 'category' => 'Family', 'image' => 'Noah.jpeg'],
            ['brand' => 'Toyota', 'model' => 'Alphard', 'numberPlate' => 'UAA 002A', 'dailyRate' => 200000, 'seats' => 8, 'category' => 'Luxury', 'image' => 'Alphard.jpeg'],
            ['brand' => 'Mercedes-Benz', 'model' => 'GLE', 'numberPlate' => 'UAA 003A', 'dailyRate' => 250000, 'seats' => 5, 'category' => 'Luxury', 'image' => 'Gle.jpeg'],
            ['brand' => 'Toyota', 'model' => 'Premio', 'numberPlate' => 'UAA 004A', 'dailyRate' => 120000, 'seats' => 5, 'category' => 'Business', 'image' => 'Premio.jpg'],
            ['brand' => 'Toyota', 'model' => 'Harrier', 'numberPlate' => 'UAA 005A', 'dailyRate' => 180000, 'seats' => 5, 'category' => 'SUV', 'image' => 'Harrier.jpg'],
            ['brand' => 'Toyota', 'model' => 'Prado', 'numberPlate' => 'UAA 006A', 'dailyRate' => 220000, 'seats' => 7, 'category' => 'SUV', 'image' => 'Prado.jpg'],
            ['brand' => 'Toyota', 'model' => 'Land Cruiser', 'numberPlate' => 'UAA 007A', 'dailyRate' => 280000, 'seats' => 7, 'category' => 'Luxury SUV', 'image' => 'Land cruiser.jpg'],
            ['brand' => 'Toyota', 'model' => 'Hilux', 'numberPlate' => 'UAA 008A', 'dailyRate' => 160000, 'seats' => 5, 'category' => 'Pickup', 'image' => 'Hilux.jpg'],
            ['brand' => 'Toyota', 'model' => 'Hilux Surf', 'numberPlate' => 'UAA 009A', 'dailyRate' => 190000, 'seats' => 5, 'category' => 'SUV', 'image' => 'Hilux Surf.jpg'],
            ['brand' => 'Toyota', 'model' => 'Fortuner', 'numberPlate' => 'UAA 010A', 'dailyRate' => 210000, 'seats' => 7, 'category' => 'SUV', 'image' => 'Toyota Fortuner.jpg'],
            ['brand' => 'Toyota', 'model' => 'Hiace', 'numberPlate' => 'UAA 011A', 'dailyRate' => 140000, 'seats' => 14, 'category' => 'Van', 'image' => 'Toyota Hiace.jpg'],
            ['brand' => 'Toyota', 'model' => 'Isis', 'numberPlate' => 'UAA 012A', 'dailyRate' => 130000, 'seats' => 7, 'category' => 'Family', 'image' => 'Toyota Isis.jpg'],
            ['brand' => 'Toyota', 'model' => 'Spacio', 'numberPlate' => 'UAA 013A', 'dailyRate' => 125000, 'seats' => 7, 'category' => 'Family', 'image' => 'Spacio.jpg'],
            ['brand' => 'Toyota', 'model' => 'Rumion', 'numberPlate' => 'UAA 014A', 'dailyRate' => 135000, 'seats' => 7, 'category' => 'Family', 'image' => 'Rumion.jpg'],
            ['brand' => 'Toyota', 'model' => 'Passo', 'numberPlate' => 'UAA 015A', 'dailyRate' => 80000, 'seats' => 5, 'category' => 'Economy', 'image' => 'Passo.jpg'],
            ['brand' => 'Toyota', 'model' => 'Auris', 'numberPlate' => 'UAA 016A', 'dailyRate' => 100000, 'seats' => 5, 'category' => 'Sedan', 'image' => 'Auris.jpg'],
            ['brand' => 'Toyota', 'model' => 'Avensis', 'numberPlate' => 'UAA 017A', 'dailyRate' => 110000, 'seats' => 5, 'category' => 'Sedan', 'image' => 'Toyota Avensis.jpg'],
            ['brand' => 'Toyota', 'model' => 'Fielder', 'numberPlate' => 'UAA 018A', 'dailyRate' => 105000, 'seats' => 5, 'category' => 'Wagon', 'image' => 'Toyota Fielder.jpg'],
            ['brand' => 'Toyota', 'model' => 'Runx', 'numberPlate' => 'UAA 019A', 'dailyRate' => 95000, 'seats' => 5, 'category' => 'Hatchback', 'image' => 'Toyota Runx.jpg'],
            ['brand' => 'Toyota', 'model' => 'Allex', 'numberPlate' => 'UAA 020A', 'dailyRate' => 90000, 'seats' => 5, 'category' => 'Hatchback', 'image' => 'Toyota Allex.jpg'],
            ['brand' => 'Jeep', 'model' => 'Wrangler', 'numberPlate' => 'UAA 021A', 'dailyRate' => 240000, 'seats' => 5, 'category' => 'SUV', 'image' => 'jeep wrangler.jpg'],
            ['brand' => 'Jeep', 'model' => 'Grand Cherokee', 'numberPlate' => 'UAA 022A', 'dailyRate' => 260000, 'seats' => 5, 'category' => 'Luxury SUV', 'image' => 'Jeep Grand Cherokee.jpg'],
            ['brand' => 'Jaguar', 'model' => 'XF 2015', 'numberPlate' => 'UAA 023A', 'dailyRate' => 300000, 'seats' => 5, 'category' => 'Luxury', 'image' => 'Jaguar xf 2015.jpg'],
            ['brand' => 'Mercedes-Benz', 'model' => 'S-Class', 'numberPlate' => 'UAA 024A', 'dailyRate' => 350000, 'seats' => 5, 'category' => 'Luxury', 'image' => 's class.jpeg'],
            ['brand' => 'Toyota', 'model' => 'RAV 4', 'numberPlate' => 'UAA 025A', 'dailyRate' => 170000, 'seats' => 5, 'category' => 'SUV', 'image' => 'Rav 4.jpeg'],
            ['brand' => 'Generic', 'model' => 'Sedan', 'numberPlate' => 'UAA 026A', 'dailyRate' => 100000, 'seats' => 5, 'category' => 'Sedan', 'image' => 'Sedan car.jpg'],
            ['brand' => 'Toyota', 'model' => 'Vanguard', 'numberPlate' => 'UAA 027A', 'dailyRate' => 200000, 'seats' => 7, 'category' => 'SUV', 'image' => 'Vangurad Toyota.jpg'],
            ['brand' => 'Toyota', 'model' => 'Kruger', 'numberPlate' => 'UAA 028A', 'dailyRate' => 195000, 'seats' => 7, 'category' => 'SUV', 'image' => 'Kruger.jpg'],
        ];

        foreach ($cars as $car) {
            Car::create([
                'brand' => $car['brand'],
                'model' => $car['model'],
                'numberPlate' => $car['numberPlate'],
                'dailyRate' => $car['dailyRate'],
                'seats' => $car['seats'],
                'category' => $car['category'],
                'carPicture' => 'images/' . $car['image'], // Store relative path to public images
                'isAvailable' => true,
            ]);
        }
    }
}

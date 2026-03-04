<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\EthiopianSynaxariumMonthly;
use Illuminate\Database\Seeder;

class EthiopianSynaxariumSeeder extends Seeder
{
    public function run(): void
    {
        $celebrations = [
            1  => ['en' => 'Liqanos (St. Bartholomew)', 'am' => 'ሊቃኖስ'],
            2  => ['en' => 'Abba Tadewos (Thaddeus)', 'am' => 'አባ ታዴዎስ'],
            3  => ['en' => 'St. Gorgoryos (Gregory the Illuminator)', 'am' => 'ቅዱስ ጎርጎርዮስ'],
            4  => ['en' => 'St. Yohannes Mesbeq (John the Baptist)', 'am' => 'ቅዱስ ዮሐንስ መስበቅ'],
            5  => ['en' => 'Abune Gebre Menfes Qidus', 'am' => 'አቡነ ገብረ መንፈስ ቅዱስ'],
            6  => ['en' => 'Lesane Kirstos & Yasay', 'am' => 'ልሳነ ክርስቶስ እና ያሴ'],
            7  => ['en' => 'Holy Trinity (Selassie)', 'am' => 'ሥላሴ'],
            8  => ['en' => 'The Four Living Creatures', 'am' => 'አርባዕቱ እንስሳ'],
            9  => ['en' => 'Angel Rufael (Raphael)', 'am' => 'ቅዱስ ሩፋኤል'],
            10 => ['en' => 'Matewos (Matthew the Evangelist)', 'am' => 'ቅዱስ ማቴዎስ'],
            11 => ['en' => 'St. Hanna (Mother of the Virgin Mary)', 'am' => 'ቅድስት ሐና'],
            12 => ['en' => 'Angel Mikael (Michael)', 'am' => 'ቅዱስ ሚካኤል'],
            13 => ['en' => 'Egziabher Ab (God the Father)', 'am' => 'እግዚአብሔር አብ'],
            14 => ['en' => 'Abune Aregawi', 'am' => 'አቡነ አረጋዊ'],
            15 => ['en' => 'Qidist Maryam (Virgin Mary - Kidane Mihret)', 'am' => 'ቅድስት ማርያም'],
            16 => ['en' => 'The Covenant of Mercy (Kidane Mihret)', 'am' => 'ኪዳነ ምሕረት'],
            17 => ['en' => 'St. Estifanos (Stephen)', 'am' => 'ቅዱስ እስጢፋኖስ'],
            18 => ['en' => 'St. Phillipos (Philip)', 'am' => 'ቅዱስ ፊልጶስ'],
            19 => ['en' => 'Angel Gebriel (Gabriel)', 'am' => 'ቅዱስ ገብርኤል'],
            20 => ['en' => 'Medhane Alem (Savior of the World)', 'am' => 'መድሃኔ ዓለም'],
            21 => ['en' => 'Dendrit Maryam (Virgin Mary)', 'am' => 'ድንግል ማርያም'],
            22 => ['en' => 'Urael (Archangel Uriel)', 'am' => 'ቅዱስ ዑራኤል'],
            23 => ['en' => 'Giorgis (St. George)', 'am' => 'ቅዱስ ጊዮርጊስ'],
            24 => ['en' => 'Abune Tekle Haymanot', 'am' => 'አቡነ ተክለ ሃይማኖት'],
            25 => ['en' => 'Mercoreos (St. Mercurius)', 'am' => 'መርቆርዮስ'],
            26 => ['en' => 'Kidist Selassie (Holy Trinity Covenant)', 'am' => 'ቅድስት ሥላሴ'],
            27 => ['en' => 'Medhane Alem (Savior of the World)', 'am' => 'መድሃኔ ዓለም'],
            28 => ['en' => 'Emanuel', 'am' => 'ኢማኑኤል'],
            29 => ['en' => 'Bale Wold (Feast of the Son)', 'am' => 'ባለ ወልድ'],
            30 => ['en' => 'Marqos (St. Mark the Evangelist)', 'am' => 'ቅዱስ ማርቆስ'],
        ];

        foreach ($celebrations as $day => $names) {
            EthiopianSynaxariumMonthly::updateOrCreate(
                ['day' => $day],
                [
                    'celebration_en' => $names['en'],
                    'celebration_am' => $names['am'],
                ]
            );
        }

        $this->command->info('Seeded 30 Ethiopian Synaxarium monthly celebrations.');
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\FasikaQuizQuestion;
use Illuminate\Database\Seeder;

class FasikaQuizQuestionSeeder extends Seeder
{
    public function run(): void
    {
        FasikaQuizQuestion::query()->delete();

        $questions = [
            // ══════════════════════════════
            // ደረጃ 1: ቀላል — 1 ነጥብ
            // ══════════════════════════════
            [
                'question'       => '"ፋሲካ" የሚለው ቃል ትርጉም በቤተክርስቲያን ትምህርት ምን ተብሎ ይፈታል?',
                'option_a'       => 'የጌታ መነሳት',
                'option_b'       => 'ማለፍ ወይም መሻገር',
                'option_c'       => 'የሞት ድል መሆን',
                'option_d'       => 'የሰው ልጅ ነፃነት',
                'correct_option' => 'b',
                'difficulty'     => 'easy',
                'points'         => 1,
                'sort_order'     => 1,
            ],
            [
                'question'       => 'በኢትዮጵያ ኦርቶዶክስ ተዋሕዶ ቤተክርስቲያን ትምህርት የዐቢይ ጾም ቀናት 55 የሆኑት ለምንድን ነው?',
                'option_a'       => 'በሐዋርያት ትዕዛዝ ስለሆነ',
                'option_b'       => 'የጾመ ነቢያት እና የጾመ ሐዋርያት ድምር ስለሆነ',
                'option_c'       => '40ው የጌታ ጾም፣ ቀሪዎቹ የሕማማት እና የሄሮድስ ጾም በመሆናቸው',
                'option_d'       => 'በቀኖና ቤተክርስቲያን የሳምንቱ ቀናት ስለማይቆጠሩ',
                'correct_option' => 'c',
                'difficulty'     => 'easy',
                'points'         => 1,
                'sort_order'     => 2,
            ],
            [
                'question'       => 'በትንሣኤ ሌሊት "ክርስቶስ ተንሥአ እሙታን" ተብሎ ሲበሰር ምእመናን የሚሰጡት ምላሽ የቱ ነው?',
                'option_a'       => 'በእውነት ተነስቷል',
                'option_b'       => 'ሃሌ ሉያ ለክርስቶስ',
                'option_c'       => 'በዐቢይ ኃይል ወሥልጣን',
                'option_d'       => 'አሳሰረ ለሰይጣን',
                'correct_option' => 'c',
                'difficulty'     => 'easy',
                'points'         => 1,
                'sort_order'     => 3,
            ],
            [
                'question'       => 'ጌታችን መድኃኒታችን ኢየሱስ ክርስቶስ የተነሳው በስንተኛው ቀን ነው?',
                'option_a'       => 'በሁለተኛው ቀን ማታ',
                'option_b'       => 'በሦስተኛው ቀን ንጋት',
                'option_c'       => 'በአራተኛው ቀን ጥዋት',
                'option_d'       => 'በሦስተኛው ቀን እኩለ ሌሊት',
                'correct_option' => 'b',
                'difficulty'     => 'easy',
                'points'         => 1,
                'sort_order'     => 4,
            ],
            [
                'question'       => 'ከትንሣኤ በዓል በፊት ያለው እሁድ "ሆሳዕና" ይባላል። የቃሉ ትርጉም ምንድን ነው?',
                'option_a'       => 'ምስጋና ለአምላክ',
                'option_b'       => 'በሰማያት ሰላም ይሁን',
                'option_c'       => 'አሁን አድን (አሁን አድነን)',
                'option_d'       => 'የእግዚአብሔር መንግሥት መጣች',
                'correct_option' => 'c',
                'difficulty'     => 'easy',
                'points'         => 1,
                'sort_order'     => 5,
            ],

            // ══════════════════════════════
            // ደረጃ 2: መካከለኛ — 2 ነጥቦች
            // ══════════════════════════════
            [
                'question'       => 'በትንሣኤ ሌሊት የሚከናወነው የቤተክርስቲያን ዑደት (ሦስት ጊዜ መዞር) ምንን ያመለክታል?',
                'option_a'       => 'የጌታን ወደ መቃብር መውረድ',
                'option_b'       => 'የጨለማው ዘመን አልፎ የብርሃን ዘመን መመጣቱን',
                'option_c'       => 'ሐዋርያት ወደ መቃብሩ መመላለሳቸውን',
                'option_d'       => 'ሥላሴን ማመስገን',
                'correct_option' => 'b',
                'difficulty'     => 'medium',
                'points'         => 2,
                'sort_order'     => 6,
            ],
            [
                'question'       => 'በትንሣኤ ዋዜማ (ቅዳሜ) የሚታደለው ቀጤማ ምንን ለማስታወስ ነው?',
                'option_a'       => 'የቤተክርስቲያንን ውበት ለመጠበቅ',
                'option_b'       => 'የኖኅ እርግብ የደረቀ ምድር መኖሩን ያበሰረችበትን ምልክት ለማሰብ',
                'option_c'       => 'የጌታ መቃብር በሣር ተሸፍኖ ስለነበር',
                'option_d'       => 'አይሁድ ጌታን ሲሰቅሉ ቀጤማ ስለተጠቀሙ',
                'correct_option' => 'b',
                'difficulty'     => 'medium',
                'points'         => 2,
                'sort_order'     => 7,
            ],
            [
                'question'       => 'ጌታችን ከተነሳ በኋላ መጀመሪያ የታየው ለማን እንደሆነ የቤተክርስቲያን ትውፊት ያስተምራል?',
                'option_a'       => 'ለማርያም መግደላዊት',
                'option_b'       => 'ለድንግል ማርያም',
                'option_c'       => 'ለቅዱስ ጴጥሮስ',
                'option_d'       => 'ለሁለቱ ደቀመዛሙርት',
                'correct_option' => 'b',
                'difficulty'     => 'medium',
                'points'         => 2,
                'sort_order'     => 8,
            ],
            [
                'question'       => 'በትንሣኤ ሌሊት የሚቀደሰው ቅዳሴ ከሌሎች በዓላት በተለየ "እኩለ ሌሊት" (6 ሰዓት) ላይ የሚጀመረው ለምንድን ነው?',
                'option_a'       => 'በሥርዓተ ቤተክርስቲያን ትዕዛዝ ስለሆነ',
                'option_b'       => 'ጌታ የተነሳው በዚያ ሰዓት መሆኑን ለማብሰር',
                'option_c'       => 'ምእመናን ለፈተና እንዳይጋለጡ',
                'option_d'       => 'ሌሊቱ የብርሃን ምሳሌ ስለሆነ',
                'correct_option' => 'b',
                'difficulty'     => 'medium',
                'points'         => 2,
                'sort_order'     => 9,
            ],
            [
                'question'       => 'በትንሣኤ ማግስት ያሉት 50 ቀናት (በዓለ ሃምሳ) ምን ተብለው ይጠራሉ?',
                'option_a'       => 'የደስታ ቀናት',
                'option_b'       => 'የትንሣኤ ሳምንታት',
                'option_c'       => 'የሰንበታት ድምር',
                'option_d'       => 'የፍስሐ ቀናት',
                'correct_option' => 'd',
                'difficulty'     => 'medium',
                'points'         => 2,
                'sort_order'     => 10,
            ],

            // ══════════════════════════════
            // ደረጃ 3: ከባድ — 3 ነጥቦች
            // ══════════════════════════════
            [
                'question'       => 'የፋሲካን በዓል ቀመር ለማስላት የምንጠቀምበት "ባሕረ ሐሳብ" የተባለውን የቁጥር ቀመር የደረሰው ሊቅ ማን ነው?',
                'option_a'       => 'ቅዱስ ያሬድ',
                'option_b'       => 'አባ ጊዮርጊስ ዘጋሥጫ',
                'option_c'       => 'ድሜጥሮስ (ሊቀ ጳጳስ)',
                'option_d'       => 'አቡሻኽር',
                'correct_option' => 'c',
                'difficulty'     => 'hard',
                'points'         => 3,
                'sort_order'     => 11,
            ],
            [
                'question'       => 'በበዓለ ሃምሳ (ከትንሣኤ በኋላ ባሉት 50 ቀናት) ጾምና ስግደት የማይፈቀደው ለምንድን ነው?',
                'option_a'       => 'ሕጉ ስለሚከለክል',
                'option_b'       => 'ሙሽራው (ክርስቶስ) ከእኛ ጋር ያለበት የደስታ ጊዜ ስለሆነ',
                'option_c'       => 'ሐዋርያት ስላልጾሙ',
                'option_d'       => 'በዓሉ ታላቅ ስለሆነ',
                'correct_option' => 'b',
                'difficulty'     => 'hard',
                'points'         => 3,
                'sort_order'     => 12,
            ],
            [
                'question'       => '"ትንሣኤ" የሚለው ቃል በነገረ መለኮት ትምህርት ከምን ጋር ተያይዞ ይተረጎማል?',
                'option_a'       => 'ከሰው ልጅ ዳግም መፈጠር ጋር',
                'option_b'       => 'ከሥጋዊ ሞት መፈታት ጋር',
                'option_c'       => 'ከኃጢአት ባርነት ነፃ መውጣት ጋር',
                'option_d'       => 'ሁሉም መልስ ናቸው',
                'correct_option' => 'd',
                'difficulty'     => 'hard',
                'points'         => 3,
                'sort_order'     => 13,
            ],
            [
                'question'       => 'በፋሲካ ሌሊት የሚነበበው የትንሣኤ ወንጌል በስንት ወገን (አቅጣጫ) ይነበባል?',
                'option_a'       => 'በአንድ (ወደ ምስራቅ ብቻ)',
                'option_b'       => 'በሁለት (ምስራቅና ምዕራብ)',
                'option_c'       => 'በአራት (ወደ አራቱም መዓዘን)',
                'option_d'       => 'እንደ ሰዓቱ ይለያያል',
                'correct_option' => 'c',
                'difficulty'     => 'hard',
                'points'         => 3,
                'sort_order'     => 14,
            ],
            [
                'question'       => 'ጌታችን በትንሣኤው "የሞትን መውጊያ" ሰበረ ሲባል ትርጉሙ ምንድን ነው?',
                'option_a'       => 'ሞት ለዘላለም ተወገደ ማለት ነው',
                'option_b'       => 'አማኞች ከሞተ ሥጋ በኋላ የዘላለም ሕይወት እንዲኖራቸው አደረገ ማለት ነው',
                'option_c'       => 'ሰይጣን መግደል አይችልም ማለት ነው',
                'option_d'       => 'የሰው ልጅ ዳግመኛ አይሞትም ማለት ነው',
                'correct_option' => 'b',
                'difficulty'     => 'hard',
                'points'         => 3,
                'sort_order'     => 15,
            ],
        ];

        foreach ($questions as $q) {
            FasikaQuizQuestion::create($q);
        }
    }
}

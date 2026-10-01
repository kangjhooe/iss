<?php

namespace Database\Seeders;

use App\Models\QuranSurah;
use Illuminate\Database\Seeder;

/**
 * Seed metadata 114 surat (tanpa teks ayat).
 * Teks ayat opsional — bisa diisi nanti via sync API.
 */
class QuranSurahSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $rows = [];
        foreach ($this->surahs() as $s) {
            $rows[] = [
                'number' => $s[0],
                'name_ar' => $s[1],
                'name_id' => $s[2],
                'name_latin' => $s[3],
                'ayah_count' => $s[4],
                'revelation_order' => $s[5],
                'revelation_type' => $s[6],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 50) as $chunk) {
            QuranSurah::query()->upsert(
                $chunk,
                ['number'],
                ['name_ar', 'name_id', 'name_latin', 'ayah_count', 'revelation_order', 'revelation_type', 'updated_at']
            );
        }
    }

    /**
     * [number, name_ar, name_id, name_latin, ayah_count, revelation_order, revelation_type]
     */
    private function surahs(): array
    {
        return [
            [1, 'الفاتحة', 'Al-Fatihah', 'Al-Fatihah', 7, 5, 'Makkiyah'],
            [2, 'البقرة', 'Al-Baqarah', 'Al-Baqarah', 286, 87, 'Madaniyah'],
            [3, 'آل عمران', 'Ali Imran', 'Ali \'Imran', 200, 89, 'Madaniyah'],
            [4, 'النساء', 'An-Nisa', 'An-Nisa\'', 176, 92, 'Madaniyah'],
            [5, 'المائدة', 'Al-Maidah', 'Al-Ma\'idah', 120, 112, 'Madaniyah'],
            [6, 'الأنعام', 'Al-An\'am', 'Al-An\'am', 165, 55, 'Makkiyah'],
            [7, 'الأعراف', 'Al-A\'raf', 'Al-A\'raf', 206, 39, 'Makkiyah'],
            [8, 'الأنفال', 'Al-Anfal', 'Al-Anfal', 75, 88, 'Madaniyah'],
            [9, 'التوبة', 'At-Taubah', 'At-Tawbah', 129, 113, 'Madaniyah'],
            [10, 'يونس', 'Yunus', 'Yunus', 109, 51, 'Makkiyah'],
            [11, 'هود', 'Hud', 'Hud', 123, 52, 'Makkiyah'],
            [12, 'يوسف', 'Yusuf', 'Yusuf', 111, 53, 'Makkiyah'],
            [13, 'الرعد', 'Ar-Ra\'d', 'Ar-Ra\'d', 43, 96, 'Madaniyah'],
            [14, 'إبراهيم', 'Ibrahim', 'Ibrahim', 52, 72, 'Makkiyah'],
            [15, 'الحجر', 'Al-Hijr', 'Al-Hijr', 99, 54, 'Makkiyah'],
            [16, 'النحل', 'An-Nahl', 'An-Nahl', 128, 70, 'Makkiyah'],
            [17, 'الإسراء', 'Al-Isra', 'Al-Isra\'', 111, 50, 'Makkiyah'],
            [18, 'الكهف', 'Al-Kahf', 'Al-Kahf', 110, 69, 'Makkiyah'],
            [19, 'مريم', 'Maryam', 'Maryam', 98, 44, 'Makkiyah'],
            [20, 'طه', 'Taha', 'Ta-Ha', 135, 45, 'Makkiyah'],
            [21, 'الأنبياء', 'Al-Anbiya', 'Al-Anbiya\'', 112, 73, 'Makkiyah'],
            [22, 'الحج', 'Al-Hajj', 'Al-Hajj', 78, 103, 'Madaniyah'],
            [23, 'المؤمنون', 'Al-Mu\'minun', 'Al-Mu\'minun', 118, 74, 'Makkiyah'],
            [24, 'النور', 'An-Nur', 'An-Nur', 64, 102, 'Madaniyah'],
            [25, 'الفرقان', 'Al-Furqan', 'Al-Furqan', 77, 42, 'Makkiyah'],
            [26, 'الشعراء', 'Asy-Syu\'ara', 'Ash-Shu\'ara\'', 227, 47, 'Makkiyah'],
            [27, 'النمل', 'An-Naml', 'An-Naml', 93, 48, 'Makkiyah'],
            [28, 'القصص', 'Al-Qasas', 'Al-Qasas', 88, 49, 'Makkiyah'],
            [29, 'العنكبوت', 'Al-\'Ankabut', 'Al-\'Ankabut', 69, 85, 'Makkiyah'],
            [30, 'الروم', 'Ar-Rum', 'Ar-Rum', 60, 84, 'Makkiyah'],
            [31, 'لقمان', 'Luqman', 'Luqman', 34, 57, 'Makkiyah'],
            [32, 'السجدة', 'As-Sajdah', 'As-Sajdah', 30, 75, 'Makkiyah'],
            [33, 'الأحزاب', 'Al-Ahzab', 'Al-Ahzab', 73, 90, 'Madaniyah'],
            [34, 'سبأ', 'Saba', 'Saba\'', 54, 58, 'Makkiyah'],
            [35, 'فاطر', 'Fatir', 'Fatir', 45, 43, 'Makkiyah'],
            [36, 'يس', 'Yasin', 'Ya-Sin', 83, 41, 'Makkiyah'],
            [37, 'الصافات', 'As-Saffat', 'As-Saffat', 182, 56, 'Makkiyah'],
            [38, 'ص', 'Sad', 'Sad', 88, 38, 'Makkiyah'],
            [39, 'الزمر', 'Az-Zumar', 'Az-Zumar', 75, 59, 'Makkiyah'],
            [40, 'غافر', 'Gafir', 'Ghafir', 85, 60, 'Makkiyah'],
            [41, 'فصلت', 'Fussilat', 'Fussilat', 54, 61, 'Makkiyah'],
            [42, 'الشورى', 'Asy-Syura', 'Ash-Shura', 53, 62, 'Makkiyah'],
            [43, 'الزخرف', 'Az-Zukhruf', 'Az-Zukhruf', 89, 63, 'Makkiyah'],
            [44, 'الدخان', 'Ad-Dukhan', 'Ad-Dukhan', 59, 64, 'Makkiyah'],
            [45, 'الجاثية', 'Al-Jasiyah', 'Al-Jathiyah', 37, 65, 'Makkiyah'],
            [46, 'الأحقاف', 'Al-Ahqaf', 'Al-Ahqaf', 35, 66, 'Makkiyah'],
            [47, 'محمد', 'Muhammad', 'Muhammad', 38, 95, 'Madaniyah'],
            [48, 'الفتح', 'Al-Fath', 'Al-Fath', 29, 111, 'Madaniyah'],
            [49, 'الحجرات', 'Al-Hujurat', 'Al-Hujurat', 18, 106, 'Madaniyah'],
            [50, 'ق', 'Qaf', 'Qaf', 45, 34, 'Makkiyah'],
            [51, 'الذاريات', 'Az-Zariyat', 'Adh-Dhariyat', 60, 67, 'Makkiyah'],
            [52, 'الطور', 'At-Tur', 'At-Tur', 49, 76, 'Makkiyah'],
            [53, 'النجم', 'An-Najm', 'An-Najm', 62, 23, 'Makkiyah'],
            [54, 'القمر', 'Al-Qamar', 'Al-Qamar', 55, 37, 'Makkiyah'],
            [55, 'الرحمن', 'Ar-Rahman', 'Ar-Rahman', 78, 97, 'Madaniyah'],
            [56, 'الواقعة', 'Al-Waqi\'ah', 'Al-Waqi\'ah', 96, 46, 'Makkiyah'],
            [57, 'الحديد', 'Al-Hadid', 'Al-Hadid', 29, 94, 'Madaniyah'],
            [58, 'المجادلة', 'Al-Mujadalah', 'Al-Mujadalah', 22, 105, 'Madaniyah'],
            [59, 'الحشر', 'Al-Hasyr', 'Al-Hashr', 24, 101, 'Madaniyah'],
            [60, 'الممتحنة', 'Al-Mumtahanah', 'Al-Mumtahanah', 13, 91, 'Madaniyah'],
            [61, 'الصف', 'As-Saff', 'As-Saff', 14, 109, 'Madaniyah'],
            [62, 'الجمعة', 'Al-Jumu\'ah', 'Al-Jumu\'ah', 11, 110, 'Madaniyah'],
            [63, 'المنافقون', 'Al-Munafiqun', 'Al-Munafiqun', 11, 104, 'Madaniyah'],
            [64, 'التغابن', 'At-Tagabun', 'At-Taghabun', 18, 108, 'Madaniyah'],
            [65, 'الطلاق', 'At-Talaq', 'At-Talaq', 12, 99, 'Madaniyah'],
            [66, 'التحريم', 'At-Tahrim', 'At-Tahrim', 12, 107, 'Madaniyah'],
            [67, 'الملك', 'Al-Mulk', 'Al-Mulk', 30, 77, 'Makkiyah'],
            [68, 'القلم', 'Al-Qalam', 'Al-Qalam', 52, 2, 'Makkiyah'],
            [69, 'الحاقة', 'Al-Haqqah', 'Al-Haqqah', 52, 78, 'Makkiyah'],
            [70, 'المعارج', 'Al-Ma\'arij', 'Al-Ma\'arij', 44, 79, 'Makkiyah'],
            [71, 'نوح', 'Nuh', 'Nuh', 28, 71, 'Makkiyah'],
            [72, 'الجن', 'Al-Jinn', 'Al-Jinn', 28, 40, 'Makkiyah'],
            [73, 'المزمل', 'Al-Muzzammil', 'Al-Muzzammil', 20, 3, 'Makkiyah'],
            [74, 'المدثر', 'Al-Muddassir', 'Al-Muddaththir', 56, 4, 'Makkiyah'],
            [75, 'القيامة', 'Al-Qiyamah', 'Al-Qiyamah', 40, 31, 'Makkiyah'],
            [76, 'الإنسان', 'Al-Insan', 'Al-Insan', 31, 98, 'Madaniyah'],
            [77, 'المرسلات', 'Al-Mursalat', 'Al-Mursalat', 50, 33, 'Makkiyah'],
            [78, 'النبأ', 'An-Naba', 'An-Naba\'', 40, 80, 'Makkiyah'],
            [79, 'النازعات', 'An-Nazi\'at', 'An-Nazi\'at', 46, 81, 'Makkiyah'],
            [80, 'عبس', 'Abasa', '\'Abasa', 42, 24, 'Makkiyah'],
            [81, 'التكوير', 'At-Takwir', 'At-Takwir', 29, 7, 'Makkiyah'],
            [82, 'الانفطار', 'Al-Infitar', 'Al-Infitar', 19, 82, 'Makkiyah'],
            [83, 'المطففين', 'Al-Mutaffifin', 'Al-Mutaffifin', 36, 86, 'Makkiyah'],
            [84, 'الانشقاق', 'Al-Insyiqaq', 'Al-Inshiqaq', 25, 83, 'Makkiyah'],
            [85, 'البروج', 'Al-Buruj', 'Al-Buruj', 22, 27, 'Makkiyah'],
            [86, 'الطارق', 'At-Tariq', 'At-Tariq', 17, 36, 'Makkiyah'],
            [87, 'الأعلى', 'Al-A\'la', 'Al-A\'la', 19, 8, 'Makkiyah'],
            [88, 'الغاشية', 'Al-Gasyiyah', 'Al-Ghashiyah', 26, 68, 'Makkiyah'],
            [89, 'الفجر', 'Al-Fajr', 'Al-Fajr', 30, 10, 'Makkiyah'],
            [90, 'البلد', 'Al-Balad', 'Al-Balad', 20, 35, 'Makkiyah'],
            [91, 'الشمس', 'Asy-Syams', 'Ash-Shams', 15, 26, 'Makkiyah'],
            [92, 'الليل', 'Al-Lail', 'Al-Layl', 21, 9, 'Makkiyah'],
            [93, 'الضحى', 'Ad-Duha', 'Ad-Duha', 11, 11, 'Makkiyah'],
            [94, 'الشرح', 'Asy-Syarh', 'Ash-Sharh', 8, 12, 'Makkiyah'],
            [95, 'التين', 'At-Tin', 'At-Tin', 8, 28, 'Makkiyah'],
            [96, 'العلق', 'Al-\'Alaq', 'Al-\'Alaq', 19, 1, 'Makkiyah'],
            [97, 'القدر', 'Al-Qadr', 'Al-Qadr', 5, 25, 'Makkiyah'],
            [98, 'البينة', 'Al-Bayyinah', 'Al-Bayyinah', 8, 100, 'Madaniyah'],
            [99, 'الزلزلة', 'Az-Zalzalah', 'Az-Zalzalah', 8, 93, 'Madaniyah'],
            [100, 'العاديات', 'Al-\'Adiyat', 'Al-\'Adiyat', 11, 14, 'Makkiyah'],
            [101, 'القارعة', 'Al-Qari\'ah', 'Al-Qari\'ah', 11, 30, 'Makkiyah'],
            [102, 'التكاثر', 'At-Takasur', 'At-Takathur', 8, 16, 'Makkiyah'],
            [103, 'العصر', 'Al-\'Asr', 'Al-\'Asr', 3, 13, 'Makkiyah'],
            [104, 'الهمزة', 'Al-Humazah', 'Al-Humazah', 9, 32, 'Makkiyah'],
            [105, 'الفيل', 'Al-Fil', 'Al-Fil', 5, 19, 'Makkiyah'],
            [106, 'قريش', 'Quraisy', 'Quraysh', 4, 29, 'Makkiyah'],
            [107, 'الماعون', 'Al-Ma\'un', 'Al-Ma\'un', 7, 17, 'Makkiyah'],
            [108, 'الكوثر', 'Al-Kausar', 'Al-Kawthar', 3, 15, 'Makkiyah'],
            [109, 'الكافرون', 'Al-Kafirun', 'Al-Kafirun', 6, 18, 'Makkiyah'],
            [110, 'النصر', 'An-Nasr', 'An-Nasr', 3, 114, 'Madaniyah'],
            [111, 'المسد', 'Al-Masad', 'Al-Masad', 5, 6, 'Makkiyah'],
            [112, 'الإخلاص', 'Al-Ikhlas', 'Al-Ikhlas', 4, 22, 'Makkiyah'],
            [113, 'الفلق', 'Al-Falaq', 'Al-Falaq', 5, 20, 'Makkiyah'],
            [114, 'الناس', 'An-Nas', 'An-Nas', 6, 21, 'Makkiyah'],
        ];
    }
}

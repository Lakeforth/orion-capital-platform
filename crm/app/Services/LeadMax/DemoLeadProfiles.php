<?php

namespace App\Services\LeadMax;

class DemoLeadProfiles
{
    private const FIRST = ['Adam', 'Ahmad', 'Ali', 'Amir', 'Anas', 'Anwar', 'Ayman', 'Bader', 'Bilal', 'Fadi', 'Faisal', 'Farid', 'Hadi', 'Hamza', 'Hassan', 'Hatem', 'Ibrahim', 'Imad', 'Jamal', 'Karim', 'Khaled', 'Majed', 'Malik', 'Marwan', 'Mohamed', 'Nabil', 'Nader', 'Nasser', 'Omar', 'Rami', 'Rashid', 'Samer', 'Samir', 'Tamer', 'Tariq', 'Walid', 'Yasser', 'Yazan', 'Yousef', 'Ziad', 'Aisha', 'Amal', 'Asma', 'Basma', 'Dalia', 'Dina', 'Farah', 'Hala', 'Hana', 'Heba', 'Huda', 'Iman', 'Laila', 'Lina', 'Maha', 'Mariam', 'Mona', 'Nadia', 'Nour', 'Rana', 'Rania', 'Rasha', 'Salma', 'Sara'];

    private const LAST = ['Abbas', 'Abdallah', 'Abu Saleh', 'Adwan', 'Akel', 'Alami', 'Amin', 'Amara', 'Amir', 'Arafat', 'Asadi', 'Awad', 'Ayoub', 'Aziz', 'Badawi', 'Bakri', 'Barakat', 'Bishara', 'Darwish', 'Dib', 'Eid', 'Fahmi', 'Farah', 'Fares', 'Fawzi', 'Ghanem', 'Habib', 'Haddad', 'Haj', 'Hakim', 'Hamdan', 'Hamed', 'Hamid', 'Hanna', 'Harb', 'Hassan', 'Hussein', 'Ibrahim', 'Issa', 'Jaber', 'Jamal', 'Kassem', 'Khalil', 'Khoury', 'Mahmoud', 'Mansour', 'Masri', 'Mousa', 'Najjar', 'Nasser', 'Nassar', 'Odeh', 'Osman', 'Qadri', 'Rahman', 'Ramadan', 'Rashid', 'Saad', 'Saleh', 'Salem', 'Shaheen', 'Shami', 'Yasin', 'Zidan'];

    public static function make(int $serial, string $account): array
    {
        // Permute the name pairs, so adjacent table rows do not repeat one name.
        $pair = ($serial * 1543 + 317) % 4096;
        $first = self::FIRST[$pair % 64];
        $last = self::LAST[intdiv($pair, 64)];
        $prefix = ['50', '52', '53', '54', '55', '58'][$serial % 6];

        return [
            'first_name' => $first, 'last_name' => $last,
            'email' => strtolower(str_replace(' ', '.', $first.'.'.$last)).$account.'@example.com',
            'phone' => '+972'.$prefix.(1000000 + ($serial * 7919 + 2451783) % 9000000),
            'source' => ['Website', 'Facebook', 'Google Ads', 'Referral', 'Instagram', 'Website', 'Google Ads'][$serial % 7],
        ];
    }

    public static function totalsSerial(string $period, string $status, int $index): int
    {
        $counts = $period === 'current' ? LeadsTotalsSeeder::CURRENT : LeadsTotalsSeeder::PREVIOUS;
        $serial = 521 + ($period === 'previous' ? array_sum(LeadsTotalsSeeder::CURRENT) : 0);
        foreach ($counts as $key => $count) {
            if ($key === $status) {
                return $serial + $index;
            }
            $serial += $count;
        }
        throw new \InvalidArgumentException('Unknown fixture status');
    }
}

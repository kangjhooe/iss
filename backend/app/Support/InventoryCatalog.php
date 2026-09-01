<?php

namespace App\Support;

final class InventoryCatalog
{
    public const TRACKING_STOCK = 'stock';

    public const TRACKING_INDIVIDUAL = 'individual';

    public const IDENTITY_COMPLETE = 'complete';

    public const IDENTITY_UNASSIGNED = 'unassigned';

    public const IDENTITY_MIGRATION_PENDING = 'migration_pending';

    public const DISPOSAL_ACTIVE = 'active';

    public const DISPOSAL_DISPOSED = 'disposed';

    /** @return list<string> */
    public static function trackingTypes(): array
    {
        return [self::TRACKING_STOCK, self::TRACKING_INDIVIDUAL];
    }

    /** @return list<string> */
    public static function ownershipTypes(): array
    {
        return [
            'Negara',
            'Pemerintah Daerah',
            'Yayasan',
            'Satuan Pendidikan',
            'Hibah',
            'Pihak Lain',
            'Belum Ditentukan',
        ];
    }

    /** @return list<string> */
    public static function acquisitionMethods(): array
    {
        return [
            'Pembelian',
            'Hibah',
            'Bantuan Pemerintah',
            'Bantuan Pemerintah Daerah',
            'Sumbangan',
            'Transfer',
            'Donasi',
            'Lainnya',
        ];
    }
}

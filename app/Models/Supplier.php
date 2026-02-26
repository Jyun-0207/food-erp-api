<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends BaseModel
{
    use SoftDeletes;

    const DELETED_AT = 'deletedAt';
    protected $table = 'suppliers';

    protected $fillable = [
        'name',
        'contactPerson',
        'email',
        'phone',
        'address',
        'taxId',
        'paymentTerms',
        'isActive',
    ];

    protected function casts(): array
    {
        return [
            'isActive' => 'boolean',
        ];
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'supplierId');
    }

    public function productBatches()
    {
        return $this->hasMany(ProductBatch::class, 'supplierId');
    }

    public function supplierPriceList()
    {
        return $this->hasOne(SupplierPriceList::class, 'supplierId');
    }

    public function accountsPayable()
    {
        return $this->hasMany(AccountsPayable::class, 'supplierId');
    }
}

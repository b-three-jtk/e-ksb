<?php
namespace App\Services\Admin;

use App\Enums\FinancingReqStatusEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Models\Financial;
use App\Models\Financing;
use App\Models\FinancingItem;
use App\Models\Installment;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Wakalah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

// App/Services/Admin/FinancingService.php
class FinancingService
{
    // Logic yang SAMA antara draft dan store, taruh di sini
    public function syncMemberData(User $user, array $memberData, Request $request): void
    {
        $user->update([
            'name'         => $memberData['name'],
            'nik'          => $memberData['nik'],
            'email'        => $memberData['email'] ?? $user->email,
            'phone_number' => $memberData['phone_number'] ?? $user->phone_number,
        ]);

        $user->member->update([
            'gender'               => $memberData['gender'] ?? $user->member->gender,
            'birth_place'          => $memberData['birth_place'] ?? $user->member->birth_place,
            'birth_date'           => $memberData['birth_date'] ?? $user->member->birth_date,
            'last_education'       => $memberData['last_education'] ?? $user->member->last_education,
            'domicile_address'     => $memberData['domicile_address'] ?? $user->member->domicile_address,
            'residential_address'  => $memberData['residential_address'] ?? $user->member->residential_address,
            'marital_status'       => $memberData['marital_status'] ?? $user->member->marital_status,
            'dependents'           => $memberData['dependents'] ?? $user->member->dependents,
        ]);

        // Sync heirs
        $user->member->heirs()->delete();
        if (!empty($memberData['heirs'])) {
            $user->member->heirs()->createMany($memberData['heirs']);
        }

        // Sync documents
        foreach (['slip_gaji' => 'income_slip_file', 'buku_tabungan' => 'bank_book_file'] as $docName => $fileField) {
            if ($request->hasFile($fileField)) {
                $user->member->memberDocs()->updateOrCreate(
                    ['doc_name' => $docName],
                    ['doc_attachment' => $request->file($fileField)->store('documents', 'public')]
                );
            }
        }

        // Sync financials
        $user->member->financials()->delete();
        Financial::create([
            'member_id'                    => $user->member->id,
            'gaji_pokok_amount'            => $memberData['gaji_pokok_amount'] ?? 0,
            'penghasilan_usaha_amount'     => $memberData['penghasilan_usaha_amount'] ?? 0,
            'penghasilan_pasangan_amount'  => $memberData['penghasilan_pasangan_amount'] ?? 0,
            'penghasilan_lainnya_amount'   => $memberData['penghasilan_lainnya_amount'] ?? 0,
            'biaya_hidup_keluarga_amount'  => $memberData['biaya_hidup_keluarga_amount'] ?? 0,
            'biaya_pendidikan_amount'      => $memberData['biaya_pendidikan_amount'] ?? 0,
            'jumlah_cicilan_amount'        => $memberData['jumlah_cicilan_amount'] ?? 0,
            'jumlah_tanggungan_amount'     => $memberData['jumlah_tanggungan_amount'] ?? 0,
            'jumlah_biaya_lainnya_amount'  => $memberData['jumlah_biaya_lainnya_amount'] ?? 0,
        ]);

        // Sync job
        $user->member->memberJobs()->delete();
        if (isset($memberData['job_title'])) {
            $user->member->memberJobs()->create([
                'employment_status'        => $memberData['employment_status'] ?? null,
                'job_title'                => $memberData['job_title'] ?? null,
                'company_or_business_name' => $memberData['company_or_business_name'] ?? null,
                'business_field'           => $memberData['business_field'] ?? null,
                'tenure_year'              => $memberData['tenure_year'] ?? null,
                'workplace_address'        => $memberData['workplace_address'] ?? null,
                'workplace_contact'        => $memberData['workplace_contact'] ?? null,
            ]);
        }
    }

    public function syncFinancingData(User $user, array $validated, Request $request, string $updatedBy): ?Financing
    {
        if (!isset($validated['financing']['name'])) return null;

        $financingData  = $validated['financing'];
        $supplierData   = $validated['supplier'] ?? null;
        $collateralData = $validated['collateral'] ?? null;

        $existingFinancing = Financing::where('member_id', $user->member->id)
            ->whereIn('status', [
                FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
                FinancingReqStatusEnum::REJECTED->value,
                FinancingReqStatusEnum::APPROVED->value,
                FinancingReqStatusEnum::APPROVED_WITH_CONDITIONS->value,
            ])
            ->latest()
            ->first();

        if ($existingFinancing) {
            // Update yang sudah ada
            $existingFinancing->update([
                'down_payment'   => $financingData['down_payment'] ?? 0,
                'akad_date'      => $financingData['akad_date'] ?? null,
                'cost_price'     => $financingData['cost_price'] ?? null,
                'margin_amount'  => $financingData['margin_amount'] ?? null,
                'payment_method' => $financingData['payment_method'] ?? null,
                'tenor'          => $financingData['tenor'] ?? null,
                'updated_by'     => $updatedBy,
                'predicted_cost_price' => $financingData['predicted_cost_price'] ?? null,
                'status'         => $financingData['status'] ?? FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
            ]);
            $financing = $existingFinancing;
        } else {
            // Buat baru kalau memang belum ada sama sekali
            $financing = Financing::create([
                'member_id'      => $user->member->id,
                'tenor'          => $financingData['tenor'] ?? null,
                'down_payment'   => $financingData['down_payment'] ?? 0,
                'akad_date'      => $financingData['akad_date'] ?? null,
                'cost_price'     => $financingData['cost_price'] ?? null,
                'margin_amount'  => $financingData['margin_amount'] ?? null,
                'payment_method' => $financingData['payment_method'] ?? null,
                'predicted_cost_price' => $financingData['predicted_cost_price'] ?? null,
                'updated_by'     => $updatedBy,
                'status'         => $financingData['status'] ?? FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
            ]);
        }

        if ($financing->status === FinancingReqStatusEnum::PENDING_REVIEW->value) {
            $financing->update(['requested_date' => now()]);
        }

        $supplier = null;
        if ($supplierData && isset($supplierData['supplier_name'])) {
            $supplier = Supplier::updateOrCreate(
                ['supplier_name' => $supplierData['supplier_name']],
                ['address' => $supplierData['address'] ?? null]
            );
        }

        $financingItem = FinancingItem::updateOrCreate(
            ['financing_id' => $financing->id],
            [
                'name'            => $financingData['name'] ?? null,
                'specification'   => $financingData['specification'] ?? null,
                'qty'             => $financingData['qty'] ?? null,
                'condition'       => $financingData['condition'] ?? null,
                'price_per_unit'  => $financingData['price_per_unit'] ?? null,
                'product_type_id' => $financingData['product_type_id'] ?? null,
                'supplier_id'     => $financingData['supplier_id'] ?? null,
            ]
        );

        if ($request->hasFile('purchase_receipt_file')) {
            $financingItem->update([
                'purchase_receipt' => $request->file('purchase_receipt_file')->store('documents', 'public'),
            ]);
        }

        if (isset($financingData['akad_wakalah_date'])) {
            $wakalah = Wakalah::updateOrCreate(
                ['financing_id' => $financing->id],
                [
                    'akad_date'       => $financingData['akad_wakalah_date'] ?? null,
                ]
            );
            if ($request->hasFile('akad_wakalah_file')) {
                $wakalah->update([
                    'signed_akad_document' => $request->file('akad_wakalah_file')->store('documents', 'public'),
                ]);
            }
        }

        if ($collateralData && isset($collateralData['collateral_type'])) {
            $financing->collateral()->updateOrCreate(
                ['financing_id' => $financing->id],
                [
                    'collateral_type'        => $collateralData['collateral_type'],
                    'owner_name'             => $collateralData['owner_name'] ?? null,
                    'estimated_market_value' => $collateralData['estimated_market_value'] ?? null,
                    'collateral_location'    => $collateralData['collateral_location'] ?? null,
                ]
            );
        }

        return $financing;
    }

    public function generateInstallments(Financing $financing): void
    {
        if (!$financing->tenor) return;

        $installmentAmount = ($financing->cost_price + $financing->margin_amount - $financing->down_payment) / $financing->tenor;
        for ($i = 1; $i <= $financing->tenor; $i++) {
            Installment::create([
                'financing_id'   => $financing->id,
                'installment_no' => $i,
                'amount'         => round($installmentAmount, 2),
                'due_date'       => $financing->akad_date->addMonths($i),
                'status'         => InstallmentPaymentScheduleStatusEnum::SCHEDULED->value,
            ]);
        }
    }
}

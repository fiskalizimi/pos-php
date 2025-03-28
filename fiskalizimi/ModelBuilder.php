<?php

namespace fiskalizimi;

use Atk\CitizenCoupon;
use Atk\PosCoupon;
use Atk\CouponItem;
use Atk\Payment;
use Atk\PaymentType;
use Atk\TaxGroup;
use Atk\CouponType;

class ModelBuilder
{
    public function getCitizenCoupon(): CitizenCoupon
    {
        $citizenCoupon = new CitizenCoupon();
        $citizenCoupon->setBusinessId(811000000);
        $citizenCoupon->setPosId(1);
        $citizenCoupon->setCouponId(10);
        $citizenCoupon->setType(CouponType::Sale);
        $citizenCoupon->setTime((new \DateTime('2024-10-01 15:30:20', new \DateTimeZone('UTC')))->getTimestamp());
        $citizenCoupon->setTotal(1820);

        $citizenCoupon->setTaxGroups([
            (new TaxGroup())->setTaxRate("C")->setTotalForTax(450)->setTotalTax(0),
            (new TaxGroup())->setTaxRate("D")->setTotalForTax(320)->setTotalTax(26),
            (new TaxGroup())->setTaxRate("E")->setTotalForTax(1850)->setTotalTax(189),
        ]);

        $citizenCoupon->setTotalTax(215);

        return $citizenCoupon;
    }

    public function getPosCoupon(): PosCoupon
    {
        $posCoupon = new PosCoupon();
        $posCoupon->setBusinessId(60100);
        $posCoupon->setCouponId(10);
        $posCoupon->setBranchId(1);
        $posCoupon->setLocation("Ferizaj");
        $posCoupon->setOperatorId("AlbanB");
        $posCoupon->setPosId(1);
        $posCoupon->setApplicationId(1234);
        $posCoupon->setVerificationNo("1234567890123456");
        $posCoupon->setType(CouponType::Sale);
        $posCoupon->setTime((new \DateTime('2024-10-01 15:30:20', new \DateTimeZone('UTC')))->getTimestamp());

        $posCoupon->setItems([
            (new CouponItem())->setName("uje rugove")->setPrice(150)->setUnit("cope")->setQuantity(3)->setTotal(450)->setTaxRate("C")->setType("TT"),
            (new CouponItem())->setName("sendviq")->setPrice(300)->setUnit("cope")->setQuantity(2)->setTotal(600)->setTaxRate("E")->setType("TT"),
            (new CouponItem())->setName("buke")->setPrice(80)->setUnit("cope")->setQuantity(4)->setTotal(320)->setTaxRate("D")->setType("TT"),
            (new CouponItem())->setName("machiato e madhe")->setPrice(150)->setUnit("cope")->setQuantity(3)->setTotal(450)->setTaxRate("E")->setType("TT"),
        ]);

        $posCoupon->setPayments([
            (new Payment())->setType(PaymentType::Cash)->setAmount(500),
            (new Payment())->setType(PaymentType::CreditCard)->setAmount(1000),
            (new Payment())->setType(PaymentType::Voucher)->setAmount(320),
        ]);

        $posCoupon->setTotal(1820);

        $posCoupon->setTaxGroups([
            (new TaxGroup())->setTaxRate("C")->setTotalForTax(450)->setTotalTax(0),
            (new TaxGroup())->setTaxRate("D")->setTotalForTax(320)->setTotalTax(26),
            (new TaxGroup())->setTaxRate("E")->setTotalForTax(1850)->setTotalTax(189),
        ]);

        $posCoupon->setTotalTax(215);
        $posCoupon->setTotalNoTax(1605);

        return $posCoupon;
    }
}
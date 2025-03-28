namespace Atk;

use Google\Protobuf\Internal\Message;

class CouponItem extends Message
{
    private $name = '';
    private $price = 0;
    private $unit = '';
    private $quantity = 0.0;
    private $total = 0;
    private $taxRate = '';
    private $type = '';

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $value): void
    {
        $this->name = $value;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $value): void
    {
        $this->price = $value;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setUnit(string $value): void
    {
        $this->unit = $value;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function setQuantity(float $value): void
    {
        $this->quantity = $value;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $value): void
    {
        $this->total = $value;
    }

    public function getTaxRate(): string
    {
        return $this->taxRate;
    }

    public function setTaxRate(string $value): void
    {
        $this->taxRate = $value;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $value): void
    {
        $this->type = $value;
    }
}
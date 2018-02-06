<?php
namespace App\Resources\Parkandfly;

use App\Helpers\DocumentHelper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Resources\AbstractMethodRequest;
use Illuminate\Support\Facades\DB;

class Parking2WrapperAbstractRequest extends AbstractMethodRequest
{
    protected $cacheDays = false;
    public $resource2Request = true;

    protected $params = [];
    protected $result;

    public function setParams(Array $params)
    {
        $this->params = $params;
    }

    public function getResult()
    {
        return $this->result;
    }

    protected function getOrderById($orderId)
    {
        try {
            return DocumentHelper::show('order', 'parking2', $orderId);
        } catch (\Exception $e) {
            $this->setErrorString('Cannot find parking2 order `'. $orderId .'`.');
        }
        return null;
    }

    protected function getProductById($productId)
    {
        try {
            // DocumentHelper::show('product', 'parking2', $productId)->toArray();
            // Need to get ALL details from the search incl. price  and other stuff
            $data = ResourceHelper::callResource2(
                'product.parking2',
                array_merge($this->params, [ResourceInterface::__ID => $productId, 'getproduct' => true])
            );

            return $data[0];
        } catch(\Exception $e) {
            $this->setErrorString('Cannot find parking2 product `' . $productId . '`.');
        }

        return null;
    }

    protected function getOrderByOrderId($orderId)
    {
        try {
            $orders = DocumentHelper::get('order', 'parking2', ['filters' => [ResourceInterface::ORDER_ID => $orderId]]);

            if (count($orders['documents']) == 0)
                $this->setErrorString('Cannot find parking2 with field "order_id" `'. $orderId .'` (note: not __id).');

            return head($orders['documents']);
        } catch (\Exception $e) {
            $this->setErrorString('Cannot find parking2 with field "order_id" = `'. $orderId .'` (note: not __id): '. $e->getMessage());
        }
        return null;
    }

    protected function getOrderByTransactionId($transactionId)
    {
        try {
            $orders = DocumentHelper::get('order', 'parking2', ['filters' => [ResourceInterface::PAYMENT_TRANSACTION_ID => $transactionId]]);

            if (count($orders['documents']) == 0)
                $this->setErrorString('Cannot find parking2 with field "payment_transaction_id" `'. $transactionId .'`.');

            return head($orders['documents']);
        } catch (\Exception $e) {
            $this->setErrorString('Cannot find parking2 with field "payment_transaction_id" `'. $transactionId .'`: '. $e->getMessage());
        }
        return null;
    }

    protected function getRightSetting($name, $user, $website)
    {
        $right = null;
        if (!$right && !empty($website))
            $right = DB::table('rights')->where('user_id', $user)->where('website_id', $website)->where('active', 1)->where('key', $name)->first();
        if (!$right && !empty($user)) {
            $right = DB::table('rights')->where('user_id', $user)->where('website_id', 0)->where('active', 1)->where('key', $name)->first();
        }
        if (!$right)
            return null;

        return $right->value;
    }
}
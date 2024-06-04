<?php

interface OrderServiceInterface
{
    public function createOrder(int $userId, array $cartItems, string $deliveryAddress): Order;
    public function getOrderStatus(int $orderId): string;
}

class OrderService implements OrderServiceInterface
{
    private $orderRepository;
    private $productRepository;
    private $warehouseRepository;
    private $logisticsService;

    public function __construct(
        OrderRepository $orderRepository,
        ProductRepository $productRepository,
        WarehouseRepository $warehouseRepository,
        LogisticsService $logisticsService
    ) {
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->warehouseRepository = $warehouseRepository;
        $this->logisticsService = $logisticsService;
    }

    public function createOrder(int $userId, array $cartItems, string $deliveryAddress): Order
    {
        // Validate and calculate total price
        $totalPrice = 0;
        foreach ($cartItems as $item) {
            $product = $this->productRepository->find($item['productId']);
            if ($product->quantity < $item['quantity']) {
                throw new Exception('Not enough stock for product ' . $product->name);
            }
            $totalPrice += $product->price * $item['quantity'];
        }

        // Create Order
        $order = $this->orderRepository->create([
            'user_id' => $userId,
            'status' => 'pending',
            'total_price' => $totalPrice,
            'created_at' => new \DateTime()
        ]);

        // Add Order Items
        foreach ($cartItems as $item) {
            $this->orderRepository->addOrderItem($order->id, $item['productId'], $item['quantity'], $item['price']);
        }

        // Register delivery
        $deliveryId = $this->logisticsService->registerDelivery($order->id, $deliveryAddress);
        $order->delivery_id = $deliveryId;

        return $order;
    }

    public function getOrderStatus(int $orderId): string
    {
        $order = $this->orderRepository->find($orderId);
        return $this->logisticsService->getDeliveryStatus($order->delivery_id);
    }
}

class LogisticsService
{
    public function registerDelivery(int $orderId, string $deliveryAddress): int
    {
        // Call external logistics API to register delivery
        // Return delivery ID
    }

    public function getDeliveryStatus(int $deliveryId): string
    {
        // Call external logistics API to get delivery status
    }
}

// Repositories and other dependencies would be implemented similarly...

?>

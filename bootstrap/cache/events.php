<?php return array (
  'Illuminate\\Foundation\\Support\\Providers\\EventServiceProvider' => 
  array (
    'App\\Events\\ReviewApprovedEvent' => 
    array (
      0 => 'App\\Listeners\\RecalculateProductRatingListener@handle',
    ),
    'App\\Events\\PaymentSuccessEvent' => 
    array (
      0 => 'App\\Listeners\\UpdateOrderOnPaymentSuccessListener@handle',
    ),
    'App\\Events\\ShipmentStatusUpdatedEvent' => 
    array (
      0 => 'App\\Listeners\\UpdateOrderOnShipmentStatusListener@handle',
    ),
  ),
);
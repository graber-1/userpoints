Userpoints
==========

The module allows managing points that can be attached to any entity type but
user entity type has UI out of the box. Points are fieldable entities, types
introduce an initial value config parameter. Point history is handled by
point entity revisions with log messages for each operation. 

The module contains the 'userpoints.points' service for points management
(See \Drupal\userpoints\Service\UserPointsServiceInterface for the list of
possible methods), it also exposes a REST resource that allows to perform
operations supported by the service.

### Getting started

1. Install the module as any other module,
2. Create a point type on /admin/structure/userpoints,
3. Grant desired user permissions,
4. Manage and / or view any user points on user/{user_id}/points (account tab available)

### REST endpoint

The endpoint is available under POST /api/userpoints and accepts a data array
with the following parameters:

* op: operation, one of (add, transfer, getQuantity, getLog),
* type: userpoints type machine ID,
* entity_type_id: (optional, default: user) the entity type ID of the entity in question,
* entity_id: the entity ID of the entity in question,
* quantity: (used for add and transfer operations) the quantity to add or transfer,
* target_entity_type_id: (used for transfer operation, optional, default: user) the entity type ID of the receiving entity,
* target_entity_id: (used for transfer operation) the entity ID of the receiving entity,
* log: (optional, used for add and transfer operations) revision log message for the operation.

The "add" operation accepts negative values for subtracting points.

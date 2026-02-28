<?php

namespace Modules\Ticketing\Repositories\Ticket;

use Modules\Ticketing\Models\Ticket;

interface TicketRepository
{
    public function store(array $data): Ticket;
    public function update(int $id, array $data): bool;
    public function findById(int $id): ?Ticket;
    public function delete(int $id): bool;
}

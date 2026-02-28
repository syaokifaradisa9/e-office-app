<?php

namespace Modules\Ticketing\Repositories\Ticket;

use Modules\Ticketing\Models\Ticket;

class EloquentTicketRepository implements TicketRepository
{
    public function store(array $data): Ticket
    {
        return Ticket::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return Ticket::where('id', $id)->update($data);
    }

    public function findById(int $id): ?Ticket
    {
        return Ticket::with(['user', 'assetItem.assetCategory', 'confirmedByUser', 'processedByUser', 'refinements'])->find($id);
    }

    public function delete(int $id): bool
    {
        return Ticket::where('id', $id)->delete();
    }
}

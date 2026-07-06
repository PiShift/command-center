<?php

namespace Tests\Unit;

use App\Livewire\KanbanBoard;
use PHPUnit\Framework\TestCase;

class KanbanBoardProjectFilterTest extends TestCase
{
    public function test_mount_parses_comma_separated_project_filter_into_project_ids(): void
    {
        $component = new KanbanBoard;
        $component->projectFilter = '4,2,2,0,abc,7';

        $component->mount();

        $this->assertSame([4, 2, 7], $component->projectIds);
    }

    public function test_updated_project_ids_syncs_query_string_value(): void
    {
        $component = new KanbanBoard;

        $component->updatedProjectIds(['9', '9', '0', '-1', '3']);

        $this->assertSame([9, 3], $component->projectIds);
        $this->assertSame('9,3', $component->projectFilter);
    }

    public function test_apply_stored_selection_does_not_override_existing_query_filter(): void
    {
        $component = new KanbanBoard;
        $component->projectFilter = '5';
        $component->projectIds = [5];

        $component->applyStoredProjectSelection('1,2,3');

        $this->assertSame([5], $component->projectIds);
        $this->assertSame('5', $component->projectFilter);
    }

    public function test_apply_stored_selection_sets_ids_when_query_filter_is_empty(): void
    {
        $component = new KanbanBoard;
        $component->projectFilter = '';
        $component->projectIds = [];

        $component->applyStoredProjectSelection('1,2,2,0');

        $this->assertSame([1, 2], $component->projectIds);
        $this->assertSame('1,2', $component->projectFilter);
    }
}

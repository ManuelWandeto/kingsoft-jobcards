<div class="filters" x-id="['tags-modal']" @tags-changed="$store.jobs.filters.tags = $event.detail" x-init="()=>{
    const toggleButtons = document.querySelectorAll('div.custom-dropdown > div.filter-action > button.filter-button')
    const dropdownMenus = document.querySelectorAll('div.custom-dropdown > div.dropdown-content')

    for (let i = 0; i < toggleButtons.length; i++) {
        window.FloatingUIDOM.autoUpdate(toggleButtons[i], dropdownMenus[i], () => {
            window.FloatingUIDOM.computePosition(toggleButtons[i], dropdownMenus[i], {
                placement: 'bottom-end', 
                middleware: [
                    window.FloatingUIDOM.offset(10),
                    window.FloatingUIDOM.flip(),
                    window.FloatingUIDOM.shift()
                ]
            }).then(({y}) => {
                Object.assign(dropdownMenus[i].style, {
                    top: `${y}px`,
                    bottom: `${y}px`
                });
            })
        }) 
    }
}">
    <div class="search dropdown">
        <i class="now-ui-icons ui-1_zoom-bold"></i>
        <input type="text" x-model="$store.jobs.filters.search.value" name="search" placeholder="Search by:">
        <a href="#" role="button" class="dropdown-toggle" data-toggle="dropdown" data-offset="-10,20" aria-haspopup="true">
            <button class="icon-button" 
                aria-expanded="false">
            </button>
            <span class="search-by" x-text="$store.jobs.filters.search.by?.slice(0, 1).toUpperCase()" x-transition x-cloak>
            </span>
        </a>
        <div class="dropdown-menu dropdown-menu-right">
            <div class="form-check">
                <input class="form-check-input" x-model="$store.jobs.filters.search.by" name="search_by" type="radio"
                    id="search_by_project" value="project" checked>
                <label class="form-check-label" for="search_by_project">
                    Project
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" x-model="$store.jobs.filters.search.by" name="search_by" type="radio"
                    id="search_by_client" value="client">
                <label class="form-check-label" for="search_by_client">
                    Client
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" x-model="$store.jobs.filters.search.by" name="search_by" type="radio"
                    id="search_by_assignee" value="assignee">
                <label class="form-check-label" for="search_by_assignee">
                    Assignee
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" x-model="$store.jobs.filters.search.by" name="search_by" type="radio"
                    id="search_by_supervisor" value="supervisor">
                <label class="form-check-label" for="search_by_supervisor">
                    Supervisor
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" x-model="$store.jobs.filters.search.by" name="search_by" type="radio"
                    id="search_by_location" value="location">
                <label class="form-check-label" for="search_by_location">
                    Location
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" x-model="$store.jobs.filters.search.by" name="search_by" type="radio"
                    id="search_by_description" value="description">
                <label class="form-check-label" for="search_by_description">
                    Jobcard description
                </label>
            </div>
            <div class="dropdown-divider"></div>
        </div>
    </div>
    <?php require('tags.php') ?>
    <div class="dropdown filter-by" x-init="()=> {
        $('div.filters div.dropdown.filter-by').on('hide.bs.dropdown', (e)=>{
            if (e.clickEvent) {
                e.preventDefault();
            }
        })
    }">
        <button class="round-icon-button dropdown-toggle" data-offset="-10,20" data-toggle="dropdown"
            aria-haspopup="true" aria-expanded="false">
            <i class="now-ui-icons design_bullet-list-67"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-right p-3" style="min-width: 250px;">
            <span class="mb-2">Filter by</span>
            <div class="dropdown-divider"></div>
            <form @submit.prevent>
                <div class="form-group mb-2">
                    <div class="custom-dropdown" x-data="{isOpen: false, priorities: ['URGENT', 'MEDIUM', 'LOW']}">
                        <div class="filter-action w-100">
                            <button type="button" class="filter-button" @click="isOpen = !isOpen">
                                Priority
                            </button>
                            <button @click="$store.jobs.filters.priority = []" 
                                class="icon-button" 
                                x-show="$store.jobs.filters.priority.length"
                            >
                                <i x-data class="now-ui-icons ui-1_simple-remove"></i>
                            </button>
                        </div>
                        <div class="dropdown-content" @click.outside="isOpen = false" id="priority-select" x-show="isOpen"
                        x-transition.scale.origin.top 
                        >
                            <template x-for="priority in priorities">
                                <div class="form-check">
                                    <label class="form-check-label">
                                        <input class="form-check-input" type="checkbox" 
                                            :value="priority"
                                            x-model="$store.jobs.filters.priority"
                                            :checked = "$store.jobs.filters.priority.includes(priority)"
                                        >
                                        <span class="form-check-sign">
                                            <span class="check"></span>
                                        </span>
                                        <span x-text="priority"></span>
                                    </label>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="form-group mb-2">
                    <div class="custom-dropdown" 
                        x-data="{isOpen: false, statuses: ['OVERDUE', 'COMPLETED', 'ONGOING', 'SCHEDULED', 'REPORTED', 'SUSPENDED', 'CANCELLED']}">
                        <div class="filter-action w-100">
                            <button type="button" class="filter-button" @click="isOpen = !isOpen">
                                Status
                            </button>
                            <button @click="$store.jobs.filters.status = ['SCHEDULED', 'ONGOING', 'OVERDUE']" 
                                class="icon-button" 
                                x-show="$store.jobs.filters.status.join() !== ['SCHEDULED', 'ONGOING', 'OVERDUE'].join()"
                            >
                                <i x-data class="now-ui-icons ui-1_simple-remove"></i>
                            </button>
                        </div>
                        <div class="dropdown-content" @click.outside="isOpen = false" id="status-select" x-show="isOpen"
                        x-transition.scale.origin.top
                        >
                            <template x-for="status in statuses">
                                <div class="form-check">
                                    <label class="form-check-label">
                                        <input class="form-check-input" type="checkbox" 
                                        :value="status"
                                        :checked = "$store.jobs.filters.status.includes(status)"
                                        x-model="$store.jobs.filters.status"
                                    >
                                        <span class="form-check-sign">
                                            <span class="check"></span>
                                        </span>
                                        <span x-text="status"></span>
                                    </label>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="filter-action w-100">
                        <button type="button" class="filter-button" @click="()=> {
                            $(`#${$id('tags-modal')}`).modal('show')
                        }">
                            Tags
                        </button>
                        <button 
                            @click="$store.jobs.filters.tags.splice(0, $store.jobs.filters.tags.length)" 
                            class="icon-button" x-show="$store.jobs.filters.tags.length"
                        >
                            <i x-data class="now-ui-icons ui-1_simple-remove"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="order-by">Order-by</label>
                    <select class="custom-select" x-model="$store.jobs.filters.orderBy">
                        <option selected value="newest">Newest</option>
                        <option value="oldest">Oldest</option>
                    </select>
                </div>
                <div class="form-group">
                    <div class="form-group">
                        <label for="duration_start">From</label>
                        <input id="duration_start" type="date" class="form-control"
                            x-model="$store.jobs.filters.duration.from">
                    </div>
                    <div class="form-group">
                        <label for="duration_end">To</label>
                        <input id="duration_end" type="date" class="form-control"
                            x-model="$store.jobs.filters.duration.to">
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <button class="btn btn-danger" style="width: 100%;" @click="$store.jobs.filters.clearFilters()">
                    Clear Filters
                </button>

            </form>
        </div>
    </div>
</div>
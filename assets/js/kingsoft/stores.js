document.addEventListener('alpine:init', async () => {
    // Initialize empty stores
    Alpine.store('jobs', {
        list: [],
        filters: {
            search: {
                by: 'project',
                value: ''
            },
            priority: [],
            status: [
                'SCHEDULED',
                'ONGOING',
                'OVERDUE'
            ],
            tags: [],
            orderBy: 'newest',
            duration: {
                from: '',
                to: ''
            },
            isEmpty() {
                const {search, priority, status, tags, duration, orderBy} = this
                if (
                    !search.value?.trim() && 
                    !priority.length && 
                    !status.join() === [
                        'SCHEDULED',
                        'ONGOING',
                        'OVERDUE'
                    ].join() && 
                    !tags.length && 
                    !duration.from &&
                    !duration.to &&
                    !orderBy == 'newest'
                ) {
                    return true;
                }
                return false
            },
            clearFilters() {
                this.orderBy = 'newest'
                this.search.by = 'project',
                this.search.value = ''
                this.priority = []
                this.status = [
                    'SCHEDULED',
                    'ONGOING',
                    'OVERDUE'
                ]
                this.tags = []
                this.duration.from = ''
                this.duration.to = ''
            }
        },
        page: 1,
        has_next_page: false,
        isLoaded: false,
        error: null,
        async getJobs() {
            this.isLoaded = false
            try {
                const {search, priority, status, tags, duration, orderBy} = this.filters
                const res = await axios.get("api/jobs/get_jobs.php", {
                    params: {
                        page: this.page,
                        ...(search.value.trim() && {['search-by']: search.by, query: search.value}),
                        ...(priority.length && {priority: priority.join(',')}),
                        ...(status.length && {status: status.join(',')}),
                        ...(tags.length && {tags: tags.join(',')}),
                        ...(duration.from && {from: duration.from}),
                        ...(duration.to && {to: duration.to}),
                        ['order-by']: orderBy
                    }
                })

                const jobs = res.data.jobs
                this.has_next_page = res.data.has_next_page
                if(!jobs?.length) {
                    if(this.filters.isEmpty()) {
                        this.error = {
                            status: 204,
                            message: 'You have no jobs, start adding some'
                        }
                        this.list = []
                        return
                    }
                    this.error = {
                        status: 404,
                        message: 'No jobs found matching your search'
                    }
                    this.list = []
                }
                this.list = this.page > 1 ? [...this.list, ...jobs] : jobs
            } catch (error) {
                this.error = {
                    status: error.response?.status ?? 500,
                    message: "Internal server error occured"
                }
                this.list = []
            } finally {
                if(this.list.length) {
                    this.error = null
                }
                this.isLoaded = true
                return
            }
        },
        addJob(job) {
            this.list.unshift({...job})
            if(this.list.length) {
                this.error = null
            }
        },
        editJob(jobId, fields) {
            index = this.list.findIndex(j => j.id == jobId);
            this.list[index] = {
                ...this.list[index],
                ...fields
            }
        },
        deleteJob(jobId) {
            index = this.list.findIndex(j => j.id == jobId);
            this.list.splice(index, 1);
        }
    })

    Alpine.store('clients', {
        list: [],
        error: null,
        isLoaded: false,
        getClient(id) {
            return this.list.find(c => c.id == id)
        },
        async getClients() {
            // Get clients
            try {
                const res = await axios.get("api/clients/get_clients.php");
                const clients = res.data
                this.list = clients;
                if(clients?.length === 0) {
                    this.error = {
                        status: 204,
                        message: "Currently no clients, start adding some"
                    }
                }
            } catch (error) {
                this.error = {
                    status: error.response?.status ?? 500,
                    message: "Internal server error occured"
                }
            } finally {
                if(this.list.length) {
                    this.error = null
                }
                this.isLoaded = true
                return
            }
        },
        addClient(client) {
            this.list.push({...client})
            if(this.list.length) {
                this.error = null
            }
        },
        editClient(clientId, fields) {
            index = this.list.findIndex(c => c.id == clientId);
            this.list[index] = {
                ...this.list[index],
                ...fields
            }
        },
        deleteClient(clientId) {
            index = this.list.findIndex(c => c.id == clientId);
            this.list.splice(index, 1);
        }
    })

    Alpine.store('users', {
        list: [],
        isLoaded: false,
        error: null,
        async getUsers() {
            try {
                const res = await axios.get("api/users/get_users.php")
                const users = res.data
                this.list = users;
            } catch (error) {
                this.error = {
                    status: error.response?.status ?? 500,
                    message: "Internal server error occured"
                }
            } finally {
                if(this.list.length) {
                    this.error = null
                }
                this.isLoaded = true
                return null
            }
        },
        getUser(id) {
            return this.list.find(u => u.id == id)
        },
        editUser(userId, fields) {
            index = this.list.findIndex(u => u.id == userId);
            this.list[index] = {
                ...this.list[index],
                ...fields
            }
        },
        addUser(user) {
            this.list.push({...user})
            if(this.list.length) {
                this.error = null
            }
        },
        deleteUser(userId) {
            index = this.list.findIndex(u => u.id == userId);
            this.list.splice(index, 1)
        }
    })

    Alpine.store('tags', {
        list: [],
        isLoaded: false,
        error: null,
        async getTags() {
            try {
                const res = await axios.get("api/tags/get_tags.php")
                const tags = res.data
                this.list = tags;
                if(!tags?.length) {
                    this.error = {
                        status: 204,
                        message: "Currently no tags, start adding some"
                    }                
                }
            } catch (error) {
                this.error = {
                    status: error.response?.status ?? 500,
                    message: "Internal server error occured"
                }
                // window.dispatchEvent(new CustomEvent('tags-error', {detail: e.response.data} ))
            } finally {
                if(this.list.length) {
                    this.error = null
                }
                this.isLoaded = true;
                return
            }
        },
        getTag(id) {
            return this.list.find(t => t.id == id)
        },
        editTag(tagId, fields) {
            index = this.list.findIndex(t => t.id == tagId);
            this.list[index] = {
                ...this.list[index],
                ...fields
            }
        },
        addTag(tag) {
            this.list.push({...tag})
            if(this.list.length) {
                this.error = null
            }
        },
        deleteTag(tagId) {
            index = this.list.findIndex(t => t.id == tagId);
            this.list.splice(index, 1)
        }
    })

    Alpine.store('reports', {
        jobsPerDay: {
            isLoaded: false,
            data: [],
            error: null,
            filters: {
                timeUnit: 'day',
                period: {
                    allTime: false,
                    from: moment().subtract(28, 'days').format('YYYY-MM-DD'),
                    to: moment().format('YYYY-MM-DD'),
                },
                clearFilters() {
                    this.timeUnit = 'day'
                    this.period.allTime = false
                    this.period.from = moment().subtract(28, 'days').format('YYYY-MM-DD'),
                    this.period.to = moment().format('YYYY-MM-DD')
                }
            },
            async getJobsPerDay() {
                try {
                    const res = await axios('api/reports/jobs_per_day.php', {
                        params: {
                            unit: this.filters.timeUnit,
                            ...(
                                this.filters.period.allTime 
                                ? {'all-time': true} 
                                : {from: this.filters.period.from, to: this.filters.period.to}
                            ),
                        }
                    })
                    if(!res.data.report) {
                        throw new Error('Uncaught error getting jobs per day report')
                    }
                    if(!res.data.report?.length) {
                        throw {
                            status: 204,
                            message: "There is currently no data for the given filters"
                        }
                    }
                    this.data = res.data
                    this.error = null
                } catch (error) {
                    this.data = []
                    if(error?.status) {
                        this.error = error
                        throw error
                    }
                    this.error = {
                        status: 500,
                        message: error
                    }
                    throw this.error
                } finally {
                    this.isLoaded = true
                }
            }
        },
        jobsPerTag: {
            isLoaded: false,
            data: [],
            error: null,
            filters: {
                tags: [],
                period: {
                    allTime: false,
                    from: moment().subtract(28, 'days').format('YYYY-MM-DD'),
                    to: moment().format('YYYY-MM-DD'),
                },
                clearFilters() {
                    this.tags.splice(0, this.tags.length),
                    this.period.allTime = false
                    this.period.from = moment().subtract(28, 'days').format('YYYY-MM-DD'),
                    this.period.to = moment().format('YYYY-MM-DD')
                }
            },
            async getJobsPerTag() {
                try {
                    const res = await axios('api/reports/jobs_per_tag.php', {
                        params: {
                            ...(this.filters.tags.length && {tags: this.filters.tags.join()}),
                            ...(
                                this.filters.period.allTime 
                                ? {'all-time': true} 
                                : {from: this.filters.period.from, to: this.filters.period.to}
                            ),
                        }
                    })
                    if(!res.data.report) {
                        throw new Error('Uncaught error getting jobs per tag day report')
                    }
                    if(!res.data.report?.length) {
                        throw {
                            status: 204,
                            message: "There is currently no data for the given filters"
                        }
                    }
                    this.data = res.data.report
                } catch (error) {
                    this.data = []
                    if(error?.status) {
                        this.error = error
                        throw error
                    }
                    this.error = {
                        status: 500,
                        message: error
                    }
                    throw this.error
                } finally {
                    this.isLoaded = true
                }
            }
        },
        jobsPerClient: {
            isLoaded: false,
            data: [],
            error: null,
            filters: {
                clients: [],
                period: {
                    allTime: false,
                    from: moment().subtract(28, 'days').format('YYYY-MM-DD'),
                    to: moment().format('YYYY-MM-DD'),
                },
                clearFilters() {
                    this.clients.splice(0, this.clients.length)
                    this.period.allTime = false
                    this.period.from = moment().subtract(28, 'days').format('YYYY-MM-DD'),
                    this.period.to = moment().format('YYYY-MM-DD')
                }
            },
            async getJobsPerClient() {
                try {
                    const res = await axios('api/reports/jobs_per_client.php', {
                        params: {
                            ...(this.filters.clients.length && {
                                clients: this.filters.clients.join()
                            }),
                            ...(
                                this.filters.period.allTime 
                                ? {'all-time': true} 
                                : {from: this.filters.period.from, to: this.filters.period.to}
                            ),
                        }
                    })
                    if(!res.data.report) {
                        throw new Error('Uncaught error getting jobs per client day report')
                    }
                    if(!res.data.report?.length) {
                        throw {
                            status: 204,
                            message: "There is currently no data for the given filters"
                        }
                    }
                    this.data = res.data.report
                } catch (error) {
                    this.data = []
                    if (error?.status) {
                        this.error = error
                        throw error
                    }
                    this.error = {
                        status: 500,
                        message: error
                    }
                    throw this.error
                } finally {
                    this.isLoaded = true
                }
            }
        },
    });
    Alpine.store('jobs').getJobs();

    Alpine.store('clients').getClients();
    
    Alpine.store('tags').getTags();

    Alpine.store('users').getUsers();
})
document.addEventListener('alpine:init', async () => {
    // Initialize empty stores
    Alpine.store('jobs', {
        list: [],
        filters: {
            search: {
                by: 'project',
                value: ''
            },
            priority: undefined,
            status: undefined,
            duration: {
                from: '',
                to: ''
            },
            clearFilters() {
                this.search.by = 'project',
                this.search.value = ''
                this.priority = undefined
                this.status = undefined
                this.duration.from = ''
                this.duration.to = ''
            }
        },
        applyJobFilters(jobs) {
            let results = jobs
            filters = this.filters;
            if (filters.search.value.trim()) {
                if (filters.search.by === 'client') {
                    results = results.filter(j => {
                        let client = Alpine.store('clients').getClient(j.client_id)
                        return client?.name.toLowerCase().includes(filters.search.value.toLowerCase()) ?? false
                    })
                }
                else if (filters.search.by === 'assignee' || filters.search.by === 'supervisor') {
                    if (filters.search.value?.trim().toLowerCase() === 'null') {
                        results = results.filter(j => filters.search.by === 'assignee' ? !j.assigned_to : !j.supervised_by)
                    } else {
                        results = results.filter(j => {
                            let user = Alpine.store('users').getUser(filters.search.by === 'assignee' ? j.assigned_to : j.supervised_by)
                            return user?.username.toLowerCase().includes(filters.search.value.toLowerCase()) ?? false
                        })
                    }
                }
                else {
                    results = results.filter(j => j[filters.search.by].toLowerCase().includes(filters.search.value.toLowerCase()))
                }
            }
            if(filters.priority?.trim()) {
                results = results.filter(j => j.priority === filters.priority)
            }
            if(filters.status?.trim()) {
                results = results.filter(j => j.status === filters.status)
            }
            if(filters.duration.from && filters.duration.to) {
                results = results.filter(j => {
                    return moment(j.end_date).isBetween(filters.duration.from, filters.duration.to, 'hour')
                })
            }
            return results
        },
        isLoaded: false,
        getJobs() {
            return this.applyJobFilters(this.list)
        },
        addJob(job) {
            this.list.push({...job})
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
        isLoaded: false,
        getClient(id) {
            return this.list.find(c => c.id == id)
        },
        addClient(client) {
            this.list.push({...client})
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
        },
        deleteUser(userId) {
            index = this.list.findIndex(u => u.id == userId);
            this.list.splice(index, 1)
        }
    })

    Alpine.store('tags', {
        list: [],
        isLoaded: false,
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
        },
        deleteTag(tagId) {
            index = this.list.findIndex(t => t.id == tagId);
            this.list.splice(index, 1)
        }
    })

    // load data async into stores
    try {
        const res = await axios.get("api/jobs/get_jobs.php")
        const jobs = res.data
        Alpine.store('jobs').list = jobs
        if(jobs?.length === 0) {
            illustrateError('jobs-error-message', './assets/img/no_data.svg', 'You have no jobs, start adding some')
        }
    } catch (error) {
        illustrateError('jobs-error-message', './assets/img/server_error.svg', "Internal server error occured")
    } finally {
        Alpine.store('jobs').isLoaded = true
    }
    
    try {
        const res = await axios.get("api/clients/get_clients.php");
        const clients = res.data
        Alpine.store('clients').list = clients;
        if(clients?.length === 0) {
            illustrateError('clients-error-message', './assets/img/no_data.svg', 'Currently no clients, start adding some')
        }
    } catch (error) {
        illustrateError('clients-error-message', './assets/img/server_error.svg', "Internal server error occured")
    } finally {
        Alpine.store('clients').isLoaded = true
    }
    
    try {
        const res = await axios.get("api/users/get_users.php")
        const users = res.data
        Alpine.store('users').list = users;
    } catch (error) {
        illustrateError('users-error-message', './assets/img/server_error.svg', "Internal server error occured")
    } finally {
        Alpine.store('users').isLoaded = true
    }

    try {
        const res = await axios.get("api/tags/get_tags.php")
        const tags = res.data
        Alpine.store('tags').list = tags;
        if(!tags?.length) {
            illustrateError('tags-error-message', './assets/img/no_data.svg', 'Currently no tags, start adding some')
        }
        
    } catch (error) {
        illustrateError("tags-error-message", './assets/img/server_error.svg', `Internal error occured`)
        window.dispatchEvent(new CustomEvent('tags-error', {detail: e.response.data} ))
    } finally {
        Alpine.store('tags').isLoaded = true;
    }
})
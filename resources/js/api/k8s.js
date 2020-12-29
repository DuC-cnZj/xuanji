export const getNamespaces = () => {
    return axios.get('/namespaces')
}

export const getNamespacesUsage = (ns) => {
    return axios.get(`/namespaces/${ns}/usage`)
}

export const createNamespace = (ns) => {
    return axios.post('/namespaces', {name: ns})
}

export const deleteNamespace = (ns) => {
    return axios.delete(`/namespaces/${ns}`)
}

export const getProjectDetail = (ns, project) => {
    return axios.get(`/namespaces/${ns}/projects/${project}`)
}

export const getNamespaceDetail = (ns) => {
    return axios.get(`/namespaces/${ns}`)
}

export const containerLogs = (ns, project, pod = null, container = null) => {
    let url = `/namespaces/${ns}/projects/${project}/logs`
    if (pod && container) {
        url += `?current=${pod}::${container}`
    }
    return axios.get(url)
}

export const getExternalIps = (ns) => {
    return axios.get(`/namespaces/${ns}/namespaces_get_external_ips`)
}

export const getSingleContainerLog = (ns, project, pod, container = '') => {
    let url = `/namespaces/${ns}/projects/${project}/logs/${pod}`;
    if (container) {
        url += `?container=${container}`
    }
    return axios.get(url)
}

export default {
    getExternalIps,
    getNamespaces,
    createNamespace,
    deleteNamespace,
}

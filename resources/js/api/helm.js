export const deploy = (ns, form) => {
    const {project:{name, id: projectId}, branch = "", code = "", commit = ""} = form

    return axios.post(`/namespaces/${ns}/projects`, {
        project: name, branch, env: code, commit, project_id: projectId
    })
}

export const deployUpgrade = (ns, project, env, branch, commit) => {
    return axios.put(`/namespaces/${ns}/projects/${project}`, {
        env: env,
        branch: branch,
        commit: commit,
    })
}

export const uninstall = (ns, project) => {
    return axios.delete(`/namespaces/${ns}/projects/${project}`)
}

export default {
    deploy,
    deployUpgrade,
    uninstall,
}

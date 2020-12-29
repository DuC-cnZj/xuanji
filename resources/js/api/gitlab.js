export const gitlabProject = ()=>{
    return axios.get('/gitlab/projects')
}

export const projectCommits = (project) => {
    return axios.get(`/gitlab/projects/${project}/commits`)
}

export const gitlabBranches = (project) => {
    return axios.get(`/gitlab/projects/${project}/branches`)
}

export const branchCommits = (project, branch) => {
    return axios.get(`/gitlab/projects/${project}/branches/${branch}/commits`)
}

export const getEnvFileContent = (project, branch) => {
    return axios.get(`/gitlab/projects/${project}/branches/${branch}/file`)
}

export const pipeline = (project, branch, commit) => {
    return axios.get(`/gitlab/projects/${project}/branches/${branch}/commits/${commit}/pipeline`)
}

export const sync = () => {
    return axios.post('/gitlab/projects/sync')
}

export default {
    sync,
    getEnvFileContent,
    gitlabProject,
    projectCommits,
    gitlabBranches,
    branchCommits,
}

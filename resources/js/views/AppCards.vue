<template>
    <div style="width: 100%;">
        <!--        创建项目空间-->
        <div>
            <el-button
                type="primary"
                icon="el-icon-plus"
                @click="nsCenterDialogVisible = true"
                class="add-ns"
                circle
            ></el-button>
            <el-button
                @click="toggleConfig"
                icon="el-icon-s-opportunity"
                :class="configVisable ? ['show-config', 'light'] : ['show-config']"
                size="medium"
                circle
            ></el-button>
        </div>
        <el-dialog
            title="创建项目空间"
            :visible.sync="nsCenterDialogVisible"
            width="30%"
            center
        >
            <el-form
                :model="ns"
                :rules="nsRules"
                ref="ruleForm"
                label-width="80px"
            >
                <el-form-item label="项目空间" prop="name" style="width: 80%">
                    <el-input v-model="ns.name"></el-input>
                </el-form-item>
            </el-form>
            <span slot="footer" class="dialog-footer">
                <el-button @click="nsCenterDialogVisible = false"
                    >取 消</el-button
                >
                <el-button type="primary" @click="addNs" :loading="btnLoading"
                    >确 定</el-button
                >
            </span>
        </el-dialog>
        <!--        创建项目空间-->

        <!-- 信息 -->
        <transition name="config-tips" mode="out-in">
            <el-row style="margin-bottom: 10px;" v-show="configVisable">
                <MarkdownEditor
                    @submit="submitMde"
                    @back="configTipsEditorVisable = false"
                    v-if="configTipsEditorVisable"
                    :content="tipMd"
                />
                <el-card v-else >
                    <!-- <div slot="header" class="clearfix" style="text-align: center;">
                        <h3>Tips:</h3>
                    </div> -->
                    <el-button
                        class="clearfix"
                        @click="configTipsEditorVisable = !configTipsEditorVisable"
                        style="float: right; padding: 3px 0"
                        type="text"
                        >Edit</el-button
                    >
                    <p v-highlight class="highlight-style" v-html="tip" v-show="tip"></p>
                    <p v-show="!tip">
                        tips...
                    </p>
                </el-card>
            </el-row>
        </transition>
        <!-- 信息 -->

        <!-- 项目列表 -->
        <el-row :gutter="20">
            <el-col
                :xs="22"
                :sm="22"
                :md="10"
                :lg="8"
                :xl="6"
                v-for="(project, index) in projects"
                :key="index"
                style="margin-bottom: 10px;"
            >
                <el-card class="box-card" shadow="hover">
                    <div slot="header" class="clearfix">
                        <span style="float: left;">
                            <span v-if="project.owned">
                                <i
                                    class="el-icon-star-on"
                                    style="color: #f9ca24;"
                                ></i>
                            </span>
                            项目空间: {{ project.namespace }}
                            <el-popover
                                v-show="project.links.length > 0"
                                placement="right"
                                title="访问地址"
                                width="300"
                                trigger="hover"
                            >
                                <el-link
                                    type="success"
                                    :href="link"
                                    v-for="link in project.links"
                                    target="_blank"
                                    :key="link"
                                    >{{ link }}</el-link
                                >
                                <i class="el-icon-link" slot="reference"></i>
                            </el-popover>
                            <el-tooltip
                                class="cpu-icon"
                                effect="dark"
                                :content="
                                    project.usage.cpu +
                                        ' ' +
                                        project.usage.memory
                                "
                                placement="top"
                            >
                                <i
                                    class="el-icon-cpu"
                                    @mouseover="
                                        getNamespacesUsage(project.id, i)
                                    "
                                ></i>
                            </el-tooltip>
                            <!-- <div style="font-size: 12px;float: right;margin-left: 3px;">
                                <el-tag>{{ project.usage.memory }}, {{ project.usage.cpu }}</el-tag>
                            </div> -->
                        </span>
                        <span
                            style="float: right;cursor: pointer;"
                            @click="
                                showDeleteDialog(project.id, project.namespace)
                            "
                        >
                            <i class="el-icon-close"></i>
                        </span>
                    </div>

                    <el-row :gutter="10">
                        <el-col
                            :md="12"
                            :sm="8"
                            v-for="(p, index) in project.projects"
                            :key="index"
                        >
                            <item-card
                                :allReady="p.all_pod_ready"
                                :name="p.name"
                                :namespace="project.namespace"
                                :gitlabProjectId="p.project_id"
                                :code.sync="p.env"
                                :branch.sync="p.branch"
                                :commit.sync="p.commit"
                                :codeType.sync="p.env_file_type"
                                :projectId="p.id"
                                :nsId="project.id"
                                :cpuAndMemory="project.usage"
                                :links="project.links"
                                @delete-project="deleteProject"
                            />
                        </el-col>
                        <el-col :md="12" :sm="8">
                            <el-button
                                round
                                class="project-btn"
                                @click="showDeployDialog(project)"
                            >
                                <i class="el-icon-plus"></i>
                            </el-button>
                        </el-col>
                    </el-row>
                </el-card>
            </el-col>
        </el-row>
        <!-- 项目列表 -->

        <el-dialog
            title="删除空间"
            :visible.sync="deleteDialogVisible"
            width="30%"
        >
            <span>确定要删除该空间吗？</span>
            <span slot="footer" class="dialog-footer">
                <el-button @click="deleteDialogVisible = false"
                    >取 消</el-button
                >
                <el-button
                    type="primary"
                    @click="deleteNs"
                    :loading="btnLoading"
                    >确 定</el-button
                >
            </span>
        </el-dialog>
        <el-drawer
            custom-class="my-drawer"
            title="添加项目"
            :visible.sync="drawer"
            size="60%"
            direction="rtl"
            :before-close="handleClose"
        >
            <sync-config />

            <pipeline-info :project="this.deployForm.project.id" :branch="this.deployForm.branch" :commit="this.deployForm.commit"></pipeline-info>
            <el-form
                label-width="80px"
                :model="deployForm"
                :rules="deployRules"
                ref="deployForm"
                style="width: 95%"
            >
                <el-form-item label="项目" prop="project">
                    <el-select
                        v-model="deployForm.project.id"
                        style="width: 80%"
                        filterable
                        placeholder="请选择"
                        @focus="getGitlabProjects"
                        @change="changeGitlabProject"
                        :loading="loading"
                    >
                        <el-option
                            v-for="item in gitlabProjects"
                            :key="item.id"
                            :label="item.name"
                            :loading="loading"
                            :value="item.id"
                        >
                        </el-option>
                    </el-select>
                </el-form-item>

                <el-form-item label="branch" prop="branch">
                    <el-select
                        v-model="deployForm.branch"
                        reserve-keyword
                        filterable
                        placeholder="请选择"
                        @focus="getBranches"
                        style="width: 80%"
                        @change="changeBranch"
                        :loading="loading"
                    >
                        <el-option
                            v-for="item in branches"
                            :key="item.name"
                            :label="item.name"
                            :value="item.name"
                        >
                        </el-option>
                    </el-select>
                </el-form-item>

                <el-form-item label="commit" prop="commit">
                    <el-select
                        v-model="deployForm.commit"
                        filterable
                        reserve-keyword
                        placeholder="请选择"
                        @focus="getCommits"
                        style="width: 80%"
                        :loading="loading"
                    >
                        <el-option
                            v-for="item in commits"
                            :key="item.id"
                            :label="item.msg"
                            :value="item.id"
                        >
                        </el-option>
                    </el-select>
                </el-form-item>

                <el-form-item label="env" prop="env" size="100%">
                    <editor
                        :code.sync="deployForm.code"
                        :type="deployForm.codeType"
                    ></editor>
                </el-form-item>

                <el-form-item>
                    <el-button
                        type="primary"
                        @click="deploy"
                        :loading="btnLoading"
                        >部署</el-button
                    >
                    <el-button @click="cancel">取消</el-button>
                </el-form-item>
            </el-form>
        </el-drawer>
    </div>
</template>

<script>
import ItemCard from "../components/ItemCard";
import Editor from "../components/Editor";
import SyncConfig from "../components/SyncConfig";
import MarkdownEditor from "../components/MarkdownEditor";
import PipelineInfo from "../components/PipelineInfo";

import {
    gitlabBranches,
    branchCommits,
    gitlabProject,
    getEnvFileContent
} from "../api/gitlab";
import { updateConfigTips, getConfigTips } from "../api/config";
import {
    deleteNamespace,
    createNamespace,
    getNamespacesUsage,
    getNamespaces,
    getExternalIps
} from "../api/k8s";
import { deploy, uninstall } from "../api/helm";

export default {
    name: "AppCards",
    components: { ItemCard, Editor, SyncConfig, MarkdownEditor, PipelineInfo },
    data() {
        return {
            tip: "",
            tipMd: "",
            configTipsEditorVisable: false,
            configVisable: false,
            btnLoading: false,
            fullscreenLoading: false,
            nsCenterDialogVisible: false,
            deleteDialogVisible: false,
            drawer: false,
            loading: false,
            projects: [],
            gitlabProjects: [],
            branches: [],
            commits: [],
            currentNs: {
                id: null,
                name: ""
            },
            ns: {
                name: ""
            },
            nsRules: {
                name: [{ required: true, message: "必填", trigger: "blur" }]
            },
            deployRules: {
                project: [
                    { required: true, message: "项目必选", trigger: "blur" }
                ],
                branch: [{ required: true, trigger: "blur", message: "必选" }],
                commit: [{ required: true, trigger: "blur", message: "必选" }]
            },
            deployForm: {
                ns: {
                    id: null,
                    name: ""
                },
                project: {
                    id: null,
                    name: null
                },
                commit: "",
                branch: "",
                code: "",
                codeType: "",
            }
        };
    },
    created() {
        this.configVisable = this.isConfigVisable()

        this.fetchProjects();
        this.getConfigTips();
    },
    methods: {
        isConfigVisable() {
            return window.localStorage.getItem("configVisable") === "on"
        },
        showConfig() {
            window.localStorage.setItem("configVisable", "on")
            this.configVisable = true
        },
        hideConfig() {
            window.localStorage.setItem("configVisable", "off")
            this.configVisable = false
        },
        toggleConfig() {
            if (window.localStorage.getItem("configVisable") === "on") {
                this.hideConfig()
                this.configVisable = false
            } else {
                this.showConfig()
                this.configVisable = true
            }
        },
        getConfigTips() {
            getConfigTips().then(({ data }) => {
                this.tip = data.data.html;
                this.tipMd = data.data.md;
            });
        },
        submitMde({ content, callback }) {
            updateConfigTips(content).then(({ data }) => {
                this.tip = data.data.html;
                this.tipMd = data.data.md;
                this.$notify.success(`修改成功`);
                 if (!this.tip) {
                    this.hideConfig()
                }
                this.configTipsEditorVisable = false;
                callback();
            });
        },
        getNamespacesUsage(nsId, i) {
            getNamespacesUsage(nsId).then(
                ({ data }) => (this.projects[i].usage = data)
            );
        },
        getProjectExternalUrls(project) {
            return project.links.join(",");
            // const {data} = await getExternalIps(nsId)
            // console.log(data)
            // this.ExternalUrls = data
            // return data.join(",")
        },
        deleteProject(obj) {
            const { nsId, projectId, callback } = obj;
            this.fullscreenLoading = true;
            uninstall(nsId, projectId)
                .then(res => {
                    this.fullscreenLoading = false;
                    this.fetchProjects();
                    this.$notify.success(`删除成功`);
                    callback();
                })
                .catch(e => {
                    callback();
                });
        },
        showDeployDialog(project) {
            this.drawer = true;
            this.deployForm.ns.name = project.namespace;
            this.deployForm.ns.id = project.id;
        },
        cancel() {
            this.resetDeployForm();
            this.drawer = false;
        },
        deploy() {
            this.$refs["deployForm"].validate(valid => {
                if (valid) {
                    this.btnLoading = true;
                    this.$notify({
                        title: "提示",
                        message: "部署大概需要2分钟左右，请耐心等待",
                        position: "top-left"
                    });
                    deploy(this.deployForm.ns.id, this.deployForm)
                        .then(res => {
                            this.$notify.success("创建成功");
                            this.btnLoading = false;
                            this.fetchProjects();
                            this.resetDeployForm();
                            this.drawer = false;
                        })
                        .catch(e => {
                            this.drawer = false;
                            this.btnLoading = false;
                        });
                } else {
                    console.log("error submit!!");
                    return false;
                }
            });
        },
        changeBranch() {
            this.deployForm.commit = "";
            this.deployForm.code = "";
            this.deployForm.codeType = "";
            this.getEnvFileContent();
        },
        async getCommits() {
            if (!this.deployForm.project || !this.deployForm.branch) {
                this.$notify.error({
                    title: "错误",
                    message: "项目和分支必选"
                });
                return;
            }
            this.loading = true;
            const { data } = await branchCommits(
                this.deployForm.project.id,
                this.deployForm.branch
            );
            this.commits = data;
            this.loading = false;
        },
        async getEnvFileContent() {
            if (!this.deployForm.code) {
                const { data } = await getEnvFileContent(
                    this.deployForm.project.id,
                    this.deployForm.branch
                );
                this.deployForm.code = data.content;
                this.deployForm.codeType = data.type;
            }
        },
        async getBranches() {
            if (!this.deployForm.project) {
                this.$notify.error({
                    title: "错误",
                    message: "请先选择一个项目"
                });
                return;
            }
            this.loading = true;
            const { data } = await gitlabBranches(this.deployForm.project.id);
            this.branches = data;
            this.loading = false;
        },
        changeGitlabProject(id) {
            this.gitlabProjects.forEach(v => {
                if (v.id === id) {
                    this.deployForm.project.name = v.name;
                }
            });
            this.deployForm.branch = "";
            this.deployForm.commit = "";
        },
        handleClose(done) {
            done();
        },
        async getGitlabProjects() {
            this.loading = true;
            const { data } = await gitlabProject();
            this.gitlabProjects = data;
            this.loading = false;
        },
        deleteNs() {
            this.btnLoading = true;
            deleteNamespace(this.currentNs.id)
                .then(() => {
                    this.$notify.success("删除成功");
                    this.btnLoading = false;
                    this.resetCurrentNs();
                    this.deleteDialogVisible = false;
                    this.fetchProjects();
                })
                .catch(e => {
                    this.btnLoading = false;
                });
        },
        showDeleteDialog(id, name) {
            this.deleteDialogVisible = true;
            this.currentNs.id = id;
            this.currentNs.name = name;
        },
        addNs() {
            this.btnLoading = true;
            createNamespace(this.ns.name)
                .then(res => {
                    this.$notify.success("创建成功");
                    this.btnLoading = false;
                    this.ns.name = "";
                    this.nsCenterDialogVisible = false;
                    this.fetchProjects();
                })
                .catch(e => {
                    this.btnLoading = false;
                });
        },
        fetchProjects() {
            const loading = this.$loading({
                lock: true,
                text: "Loading",
                spinner: "el-icon-loading",
                background: "rgba(0, 0, 0, 0.7)"
            });

            getNamespaces().then(res => {
                this.projects = res.data;
                loading.close();
            });
        },
        resetCurrentNs() {
            this.currentNs = {
                id: null,
                name: ""
            };
        },
        resetDeployForm() {
            this.deployForm.ns = {
                id: null,
                name: ""
            };
            this.deployForm.project = {
                id: null,
                name: ""
            };
            this.deployForm.branch = "";
            this.deployForm.code = "";
            this.deployForm.codeType = "";
            this.deployForm.commit = "";
        }
    }
};
</script>

<style lang="scss">
.highlight-style {
    line-height: 1.5em;

    & > h1 {
        line-height: 2em;
    }

    & > blockquote {
        padding: 10px 20px 10px 20px;
        margin: 10px 0;
        border-left: 3px solid #336cff;
        background: #e9e9ff;
        color: #336cff;
        font-size: 1.1rem;
    }
}

.my-drawer {
    overflow: auto;
}
.app-item {
    margin-right: 10px;
    margin-bottom: 10px;
}

.apps {
    margin: 20px auto 0;
    max-width: 1280px;
    display: flex;
    flex-wrap: wrap;
}

.add-ns {
    z-index: 999;
    position: fixed;
    top: 80px;
    right: 20px;
    transition: 0.5s;
}

.show-config {
    font-size: 20px;
    z-index: 999;
    position: fixed;
    top: 150px;
    right: 20px;
    transition: 0.5s;
}

.show-config:hover,
.add-ns:hover {
    transform: scale(1.2);
    box-shadow: 0 0 2px #55efc4;
}

.show-config:hover, .show-config:focus{
    color: #f6e58d;
}
.clearfix:before,
.clearfix:after {
    display: table;
    content: "";
}
.clearfix:after {
    clear: both;
}

.box-card {
    overflow: auto !important;
    height: 280px;
    width: 100%;
}

.cpu-icon:hover {
    text-shadow: 0 0 5px red;
}
.config-tips-enter-active,
.config-tips-leave-active {
    transition: opacity 0.3s;
}
.config-tips-enter, .config-tips-leave-to /* .fade-leave-active below version 2.1.8 */ {
    opacity: 0;
}
.light {
    color: #f6e58d;
    text-shadow: 0 0 5px red;
    background-color: #ecf5ff;
}
</style>

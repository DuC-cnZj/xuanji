<template>
    <div class="case-dialog-div">
        <transition>
            <el-dialog
                :title="name + '(' + namespace + ')'"
                :visible.sync="centerDialogVisible"
                v-el-drag-dialog
                v-el-drag-dialog-width
                :modal="false"
                :lock-scroll="false"
                @close="closeDialog"
                :close-on-click-modal="false"
                custom-class="case-dialog-class"
                center
            >
                <el-tabs
                    v-model="activeName"
                    @tab-click="tabClick"
                    :stretch="true"
                >
                    <!-- 容器日志 -->
                    <el-tab-pane label="容器日志" name="log">
                        <el-row>
                            <el-radio
                                v-model="log.current"
                                @change="getContainerLog"
                                :label="container.container_name"
                                v-for="container in log.containers"
                                :key="container.container_name"
                            >
                                <i
                                    class="el-icon-circle-check"
                                    style="color:#32ff7e"
                                    v-if="container.ready"
                                    >Active</i
                                >
                                <i
                                    class="el-icon-circle-close"
                                    style="color:#ff4d4d"
                                    v-else
                                    >Not Ready</i
                                >
                                {{ container.name }}({{ container.container }})
                            </el-radio>
                        </el-row>

                        <p
                            id="logcontent"
                            ref="logcontent"
                            v-html="log.data"
                            v-highlight
                            style="max-height: 500px;overflow-y:auto;"
                        ></p>
                        <el-row style="margin-top: 10px;">
                            <el-button
                                icon="el-icon-refresh"
                                @click="getlog"
                                :loading="btnLoading"
                                circle
                            ></el-button>
                        </el-row>
                    </el-tab-pane>
                    <!-- 容器日志 -->

                    <!-- 命令行 -->
                    <el-tab-pane label="命令行" name="shell">
                        <el-row>
                            <el-radio
                                v-model="shell.current"
                                @change="getShellContainerLog"
                                :label="container.container_name"
                                v-for="container in log.containers"
                                :key="container.container_name"
                            >
                                <i
                                    class="el-icon-circle-check"
                                    style="color:#32ff7e"
                                    v-if="container.ready"
                                    >Active</i
                                >
                                <i
                                    class="el-icon-circle-close"
                                    style="color:#ff4d4d"
                                    v-else
                                    >Not Ready</i
                                >
                                {{ container.name }}({{ container.container }})
                            </el-radio>
                        </el-row>
                        <shell
                            :namespace="namespace"
                            :pod="currentPod"
                            :container="currentContainer"
                            :ready="shell.ready"
                            v-if="shell.ready"
                            :enabled="shellEnabled"
                        />
                    </el-tab-pane>
                    <!-- 命令行 -->

                    <!-- 配置更新 -->
                    <el-tab-pane label="配置更新" name="config">
                        <sync-config />
                        <el-form
                            :model="deployForm"
                            :rules="deployRules"
                            ref="deployForm"
                        >
                            <el-form-item prop="env" size="100%">
                                <editor :code.sync="deployForm.code" :type="deployForm.codeType"></editor>
                            </el-form-item>
                            <el-form-item>
                                <el-button
                                    type="primary"
                                    @click="deploy"
                                    :loading="btnLoading"
                                    >更新</el-button
                                >
                                <el-button @click="cancel">重置</el-button>
                            </el-form-item>
                        </el-form>
                    </el-tab-pane>
                    <!-- 配置更新 -->

                    <!-- 详细信息 -->
                    <el-tab-pane label="详细信息" name="info">
                        <div v-if="detail">
                            <!-- cpu memory -->
                            <div>
                                <i class="el-icon-cpu"></i>
                                <strong>系统信息: </strong>
                                {{ cpuAndMemory.cpu }}
                                {{ cpuAndMemory.memory }}
                            </div>
                            <!-- cpu memory -->

                            <!-- commit web url & title -->
                            <div>
                                <i class="el-icon-link"></i>
                                <strong>commit: </strong>
                                <el-link
                                    type="primary"
                                    :href="detail.web_url"
                                    target="_blank"
                                    >{{ detail.title }}
                                </el-link>
                                by {{ detail.commiter_name }}
                            </div>
                            <!-- commit web url & title -->

                            <!-- commit date -->
                            <div>
                                <i class="el-icon-date"></i>
                                <strong>提交日期: </strong>
                                {{ detail.committed_date }}
                            </div>
                            <!-- commit date -->

                            <!-- creator -->
                            <div>
                                <i class="el-icon-user"></i>
                                <strong>部署人: </strong>
                                {{ detail.creator }}
                            </div>
                            <!-- creator -->
                        </div>

                        <div
                            v-if="
                                detail && detail.pods && detail.pods.length > 0
                            "
                        >
                            <el-card
                                style="max-height: 500px;overflow-y: auto;"
                                shadow="hover"
                            >
                                <div
                                    v-for="(project, index) in detail.pods"
                                    :key="project.name"
                                >
                                    <!-- pod name -->
                                    <p>
                                        <i class="el-icon-date"></i>
                                        <strong>容器名称：</strong>
                                        <span>{{ project.name }}</span>
                                    </p>
                                    <!-- pod name -->

                                    <!-- status -->
                                    <div>
                                        <i class="el-icon-coffee-cup"></i>
                                        <strong>status: </strong>
                                        <i
                                            class="el-icon-circle-check"
                                            style="color:#32ff7e"
                                            v-if="project.ready"
                                            >Active</i
                                        >
                                        <i
                                            class="el-icon-circle-close"
                                            style="color:#ff4d4d"
                                            v-else
                                            >Not Ready</i
                                        >
                                    </div>
                                    <!-- status -->

                                    <!-- links -->
                                    <div>
                                        <i class="el-icon-link"></i>
                                        <strong>links: </strong>
                                        <ul>
                                            <li
                                                style="margin-left: 30px"
                                                v-for="(link,
                                                index) in project.links"
                                                :key="index"
                                            >
                                                <el-link
                                                    type="success"
                                                    :href="link"
                                                    target="_blank"
                                                    >{{ link }}</el-link
                                                >
                                            </li>
                                        </ul>
                                    </div>
                                    <!-- links -->

                                    <!-- created_at -->
                                    <p>
                                        <i class="el-icon-date"></i>
                                        创建于 {{ project.created_at }}
                                    </p>
                                    <!-- created_at -->

                                    <!-- image -->
                                    <div>
                                        <i class="el-icon-star-off"></i>
                                        <strong>image: </strong>
                                        <span
                                            v-for="(image,
                                            index) in project.images"
                                            :key="index"
                                        >
                                            {{ image }}
                                        </span>
                                    </div>
                                    <!-- image -->

                                    <!-- labels -->
                                    <div>
                                        <i class="el-icon-collection-tag"></i>
                                        <strong>labels: </strong>
                                        <ul>
                                            <li
                                                style="margin-left: 30px;"
                                                v-for="(value,
                                                key,
                                                index) of project.labels"
                                                :key="index"
                                            >
                                                {{ key }}: {{ value }}
                                            </li>
                                        </ul>
                                    </div>
                                    <!-- labels -->

                                    <el-divider
                                        v-if="index != detail.pods.length - 1"
                                    ></el-divider>
                                </div>
                            </el-card>
                        </div>
                        <el-row style="margin-top: 10px;">
                            <el-button
                                class="uninstall"
                                type="danger"
                                icon="el-icon-delete"
                                @click="showUninstallDialog"
                                circle
                            ></el-button>
                        </el-row>
                        <el-dialog
                            :append-to-body="true"
                            :title="'确定要删除project: ' + name + '?'"
                            :visible.sync="uninstallDialogVisible"
                            width="30%"
                            center
                        >
                            <el-alert
                                show-icon
                                :closable="false"
                                :title="'删除' + name + '的一切信息'"
                                type="error"
                            >
                            </el-alert>
                            <span slot="footer" class="dialog-footer">
                                <el-button
                                    @click="uninstallDialogVisible = false"
                                    >取 消</el-button
                                >
                                <el-button
                                    type="primary"
                                    @click="deleteProject"
                                    :loading="btnLoading"
                                    >确 定</el-button
                                >
                            </span>
                        </el-dialog>
                    </el-tab-pane>
                    <!-- 详细信息 -->
                </el-tabs>
            </el-dialog>
        </transition>

        <el-button round class="project-btn" @click="showDialog">
            <el-tooltip
                class="item"
                effect="dark"
                content="All Container Success"
                placement="top"
                v-if="log.allReady"
            >
                <i
                    class="el-icon-circle-check"
                    style="color:#32ff7e;margin-left:10px;"
                ></i>
            </el-tooltip>
            <el-tooltip
                class="item"
                effect="dark"
                content="Not Ready"
                placement="top"
                v-else
            >
                <i
                    class="el-icon-warning-outline"
                    style="color:#fffa65;margin-left:10px;"
                ></i>
            </el-tooltip>
            {{ name }}
        </el-button>
    </div>
</template>

<script>
import elDragDialog from "../directive/el-drag-dialog";
import elDragDialogWidth from "../directive/el-drag-dialog-width";
import Editor from "./Editor";
import Shell from "./Shell";
import SyncConfig from "./SyncConfig";

import { deployUpgrade } from "../api/helm";
import {
    containerLogs,
    getSingleContainerLog,
    getNamespacesUsage,
    getProjectDetail
} from "../api/k8s";

export default {
    props: [
        "allReady",
        "name",
        "namespace",
        "code",
        "codeType",
        "projectId",
        "nsId",
        "cpuAndMemory",
        "links"
    ],
    name: "ItemCard",
    directives: { elDragDialog, elDragDialogWidth },
    components: { Editor, Shell, SyncConfig },
    computed: {
        currentContainer() {
            let [pod, container] = this.shell.current.split("::");
            return container;
        },
        currentPod() {
            let [pod, container] = this.shell.current.split("::");
            return pod;
        }
    },
    created() {
        this.log.allReady = this.allReady
    },
    data() {
        return {
            detail: null,
            log: {
                current: "",
                containers: [],
                data: "",
                ready: "",
                allReady: false
            },
            shell: {
                current: "",
                ready: ""
            },
            shellEnabled: false,
            btnLoading: false,
            fullscreenLoading: false,
            deployForm: {
                code: "",
                codeType: this.codeType
            },
            deployRules: {
                code: [{ required: true, trigger: "blur", message: "必填" }]
            },
            uninstallDialogVisible: false,
            centerDialogVisible: false,
            activeName: "log"
        };
    },
    methods: {
        closeDialog() {
            this.shellEnabled = false;
            console.log("close");
        },
        getProjectDetail() {
            getProjectDetail(this.nsId, this.projectId).then(({ data }) => {
                console.log(data);
                this.detail = data;
            });
        },
        getContainerLog(label) {
            let [pod, container] = label.split("::");
            getSingleContainerLog(
                this.nsId,
                this.projectId,
                pod,
                container
            ).then(({ data }) => {
                this.log.data = data.log;
                this.log.ready = data.ready;
                this.scrollToBottom();
            });
        },
        getShellContainerLog(label) {
            let [pod, container] = label.split("::");
            getSingleContainerLog(
                this.nsId,
                this.projectId,
                pod,
                container
            ).then(({ data }) => {
                this.shell.ready = data.ready;
            });
        },
        showDialog() {
            this.getlog();
            this.centerDialogVisible = true;
        },
        scrollToBottom() {
            this.$nextTick(() => {
                this.$refs.logcontent.scrollTop = this.$refs.logcontent.scrollHeight;
            });
        },
        tabClick(tab, event) {
            switch (tab.name) {
                case "config":
                    this.shellEnabled = false;
                    this.deployForm.code = this.code;
                    this.deployForm.codeType = this.codeType;
                    break;
                case "log":
                    this.shellEnabled = false;
                    if (!this.log.current) {
                        this.getlog();
                    }
                    break;
                case "shell":
                    this.shellEnabled = true;
                    break;
                case "info":
                    this.getProjectDetail();
                    break;
            }
        },
        getlog() {
            this.btnLoading = true;
            let [pod, container] = this.log.current.split("::");
            containerLogs(this.nsId, this.projectId, pod, container).then(
                ({ data }) => {
                    this.log.data = data.log;
                    this.log.containers = data.containers;
                    this.log.current = data.current;
                    this.log.allReady = data.all_ready;
                    this.btnLoading = false;
                    this.log.ready = data.ready;

                    this.shell.current = data.current;
                    this.shell.ready = data.ready;

                    this.scrollToBottom();
                }
            );
        },
        deleteProject() {
            this.btnLoading = true;
            this.$emit("delete-project", {
                nsId: this.nsId,
                projectId: this.projectId,
                callback: () => {
                    this.uninstallDialogVisible = false;
                    this.centerDialogVisible = false;
                    this.btnLoading = false;
                }
            });
        },
        showUninstallDialog() {
            this.uninstallDialogVisible = true;
        },
        cancel() {
            this.deployForm.code = this.code;
        },
        handleClick(tab, event) {
            console.log(tab, event);
        },
        deploy() {
            this.btnLoading = true;
            deployUpgrade(this.nsId, this.projectId, this.deployForm.code)
                .then(res => {
                    this.$emit("update:code", res.data.env);
                    this.deployForm.code = res.data.env;
                    this.deployForm.codeType = res.data.config_snapshot.config_file_type;
                    this.$notify.success("配置更新成功");
                    this.btnLoading = false;
                })
                .catch(e => {
                    this.btnLoading = false;
                });
        },
        resetDeployForm() {
            this.deployForm = {
                code: "",
                codeType: ""
            };
        }
    }
};
</script>

<style>
#logcontent::-webkit-scrollbar {
    height: 9px !important;
    width: 9px !important;
}

#logcontent::-webkit-scrollbar-thumb {
    border-style: dashed;
    background-color: rgba(157, 165, 183, 0.4);
    border-color: transparent;
    border-width: 1.5px;
    background-clip: padding-box;
}

#logcontent::-webkit-scrollbar-thumb:hover {
    background: rgba(157, 165, 183, 0.7);
}
.case-dialog-class {
    pointer-events: auto;
}

.case-dialog-div > .el-dialog__wrapper {
    pointer-events: none;
}
</style>

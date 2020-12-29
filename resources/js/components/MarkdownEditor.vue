<template>
    <div>
        <el-row :gutter="20">
            <el-col :span="20">
                <textarea id="simpleMde-xuanji"></textarea>
            </el-col>
            <el-col :span="4">
                <el-button type="primary" plain @click="submit"
                    >提交</el-button
                >
                <el-button type="info" plain @click="back"
                    >返回</el-button
                >
            </el-col>
        </el-row>
    </div>
</template>

<script>
import EasyMDE from "easymde";
import hljs from "highlight.js";
window.hljs = hljs;

export default {
    props: ['content'],
    data() {
        return {
            mde: null
        };
    },
    mounted() {
        this.mde = new EasyMDE({
            autoDownloadFontAwesome: false,
            renderingConfig: {
                singleLineBreaks: false,
                codeSyntaxHighlighting: true
            },
            element: document.getElementById("simpleMde-xuanji"),
            autosave: {
                enabled: true,
                uniqueId: "xuanji"
            },
            hideIcons: ["guide"],
            sideBySideFullscreen: false
        });
        this.mde.value(this.content)
        this.mde.toggleSideBySide();
    },
    methods: {
        submit() {
            this.$emit("submit", {content:this.mde.value(), callback:() => this.mde.value("")});
        },

        back() {
            this.mde.value(this.content)
            this.$emit("back")
        }
    }
};
</script>

<style scoped>
@import "~easymde/dist/easymde.min.css";
@import "~highlight.js/styles/atom-one-dark.css";
</style>

<template>
    <div class="duc">
        <codemirror :value="code" :options="cmOption" @input="onCmCodeChange"/>
    </div>
</template>

<script>
    import dedent from 'dedent'
    import { codemirror } from 'vue-codemirror'

    // import base style
    import 'codemirror/lib/codemirror.css'

    // language
    import 'codemirror/mode/yaml/yaml.js'
    import 'codemirror/mode/php/php.js'

    // theme css
    import 'codemirror/theme/material-darker.css'
    import 'codemirror/theme/dracula.css'
    // import 'codemirror/theme/darcula.css'
    // import 'codemirror/theme/monokai.css'

    // require active-line.js
    import'codemirror/addon/selection/active-line.js'

    // styleSelectedText
    import'codemirror/addon/selection/mark-selection.js'
    import'codemirror/addon/search/searchcursor.js'

    // hint
    import'codemirror/addon/hint/show-hint.js'
    import'codemirror/addon/hint/show-hint.css'
    import'codemirror/addon/hint/javascript-hint.js'
    import'codemirror/addon/selection/active-line.js'

    // highlightSelectionMatches
    import'codemirror/addon/scroll/annotatescrollbar.js'
    import'codemirror/addon/search/matchesonscrollbar.js'
    import'codemirror/addon/search/searchcursor.js'
    import'codemirror/addon/search/match-highlighter.js'

    // keyMap
    import'codemirror/mode/clike/clike.js'
    import'codemirror/addon/edit/matchbrackets.js'
    import'codemirror/addon/comment/comment.js'
    import'codemirror/addon/dialog/dialog.js'
    import'codemirror/addon/dialog/dialog.css'
    import'codemirror/addon/search/searchcursor.js'
    import'codemirror/addon/search/search.js'
    import'codemirror/keymap/sublime.js'

    // foldGutter
    import'codemirror/addon/fold/foldgutter.css'
    import'codemirror/addon/fold/brace-fold.js'
    import'codemirror/addon/fold/comment-fold.js'
    import'codemirror/addon/fold/foldcode.js'
    import'codemirror/addon/fold/foldgutter.js'
    import'codemirror/addon/fold/indent-fold.js'
    // import'codemirror/addon/fold/markdown-fold.js'
    // import'codemirror/addon/fold/xml-fold.js'

    export default {
        props: {
            code: {
                type: String,
                default: ""
            },
            type: {
                type: String,
                default: "text/x-yaml"
            }
        },

        components: {
            codemirror
        },

        mounted() {
            setTimeout(() => {
                this.styleSelectedText = true,
                    this.cmOption.styleActiveLine = true
            }, 1800)
        },

        watch: {
            type(newT, oldT) {
                this.cmOption.mode = this.getMode()
                this.cmOption.theme = this.setTheme()
            }
        },

        data() {
            return {
                cmOption: {
                    tabSize: 4,
                    styleActiveLine: false,
                    lineNumbers: true,
                    styleSelectedText: false,
                    line: true,
                    foldGutter: true,
                    gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
                    highlightSelectionMatches: { showToken: /\w/, annotateScrollbar: true },
                    mode: this.getMode(),
                    // hint.js options
                    hintOptions:{
                        // 当匹配只有一项的时候是否自动补全
                        completeSingle: false
                    },
                    //快捷键 可提供三种模式 sublime、emacs、vim
                    keyMap: "sublime",
                    matchBrackets: true,
                    showCursorWhenSelecting: true,
                    theme: this.setTheme(),
                    extraKeys: { "Ctrl": "autocomplete" }
                }
            }
        },

        methods: {
            setTheme() {
                return this.type === 'php' ? 'material-darker' : 'dracula'
            },
            getMode() {
                switch(this.type){
                    case "php":
                        return "application/x-httpd-php"
                    case "env":
                    case "yaml":
                    default:
                        return "text/x-yaml"
                }
            },
            onCmCodeChange(newCode) {
                this.$emit("update:code", newCode)
            }
        },
    }
</script>

<style>
    .duc {
        font-family: 'my-dankmono';
        line-height: 20px;
        font-size: 12px;
    }

    .CodeMirror {
        height: 400px;
    }

    .CodeMirror-focused .cm-matchhighlight {
        background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAYAAABytg0kAAAAFklEQVQI12NgYGBgkKzc8x9CMDAwAAAmhwSbidEoSQAAAABJRU5ErkJggg==);
        background-position: bottom;
        background-repeat: repeat-x;
    }
    .cm-matchhighlight {
        background-color: lightgreen;
    }
    .CodeMirror-selection-highlight-scrollbar {
        background-color: green;
    }
</style>

<template>
    <div style="max-height: 400px;">
        <div ref="terminal" id="terminal"></div>
    </div>
</template>

<script>
import { debounce } from "lodash";
import { Terminal } from "xterm";
import { FitAddon } from "xterm-addon-fit";
import { takeUntil } from "rxjs/operators";
import { ReplaySubject, Subject } from "rxjs";
import SockJS from "sockjs-client";
import { handleExec } from "../api/shell";

import "xterm/css/xterm.css";

export default {
    props: ["namespace", "pod", "container", "ready", "enabled"],
    name: "WebShell",
    data() {
        return {
            connSubject_: new ReplaySubject(100),
            connectionClosed_: false,
            incommingMessage$_: new Subject(),
            connecting_: false,
            unsubscribe_: new Subject(),
            connected_: false,
            conn_: null,
            id: null,
            term: null
        };
    },
    methods: {
        setupConnection() {
            if (!this.ready || this.connecting_ || !this.enabled) {
                return;
            }

            this.connecting_ = true;
            this.connectionClosed_ = false;
            handleExec({
                namespace: this.namespace,
                pod: this.pod,
                container: this.container
            }).then(({ data }) => {
                this.id = data.id;

                this.conn_ = new SockJS(
                    `${window.MIX_SHELL_SOCKET_URL}/api/sockjs?${this.id}`
                );
                this.conn_.onopen = this.onConnectionOpen.bind(this, this.id);
                this.conn_.onmessage = this.onConnectionMessage.bind(this);
                this.conn_.onclose = this.onConnectionClose.bind(this);
            });
        },
        onConnectionMessage(evt) {
            const msg = JSON.parse(evt.data);
            console.log(msg);
            this.connSubject_.next(msg);
        },
        onConnectionClose() {
            if (!this.connected_) {
                return;
            }
            this.conn_.close();
            this.connected_ = false;
            this.connecting_ = false;
            this.connectionClosed_ = true;
        },
        initTerm() {
            if (this.connSubject_) {
                this.connSubject_.complete();
                this.connSubject_ = new ReplaySubject(100);
            }
            if (this.term) {
                this.term.dispose();
            }
            this.term = new Terminal({
                fontSize: 14,
                fontFamily: 'Consolas, "Courier New", monospace',
                bellStyle: "sound",
                cursorBlink: true
            });

            const fitAddon = new FitAddon();
            this.term.open(this.$refs.terminal);
            this.term.loadAddon(fitAddon);
            this.debouncedFit_ = debounce(() => {
                fitAddon.fit();
            }, 300);
            window.addEventListener("resize", () => this.debouncedFit_());

            this.connSubject_
                .pipe(takeUntil(this.unsubscribe_))
                .subscribe(frame => {
                    this.handleConnectionMessage(frame);
                });
            this.term.onData(this.onTerminalSendString.bind(this));
            this.term.onResize(this.onTerminalResize.bind(this));
            this.term.onKey(event => {
                console.log(event);
            });
        },
        handleConnectionMessage(frame) {
            if (frame.Op === "stdout") {
                this.term.write(frame.Data);
            }
            this.incommingMessage$_.next(frame);
        },
        onTerminalResize() {
            if (this.connected_) {
                console.log(this.term);
                this.conn_.send(
                    JSON.stringify({
                        Op: "resize",
                        Cols: this.term.cols,
                        Rows: this.term.rows
                    })
                );
            }
        },
        onTerminalSendString(str) {
            if (this.connected_) {
                this.conn_.send(
                    JSON.stringify({
                        Op: "stdin",
                        Data: str,
                        Cols: this.term.cols,
                        Rows: this.term.rows
                    })
                );
            }
        },
        onConnectionOpen(id) {
            console.log("onConnectionOpen: ", id);
            let startData = {
                Op: "bind",
                SessionID: id
            };
            this.connected_ = true;
            this.conn_.send(JSON.stringify(startData));
            this.connSubject_.next(startData);
            this.connected_ = true;
            this.connecting_ = false;
            this.connectionClosed_ = false;

            // Make sure the terminal is with correct display size.
            this.onTerminalResize();

            // Focus on connection
            this.term.focus();
        },
        disconnect() {
            if (this.conn_) {
                this.conn_.close();
            }

            if (this.connSubject_) {
                this.connSubject_.complete();
                this.connSubject_ = new ReplaySubject(100);
            }

            if (this.term) {
                this.term.dispose();
            }

            this.incommingMessage$_.complete();
            this.incommingMessage$_ = new Subject();
        }
    },
    mounted() {
        if (this.conn_ && this.connected_) {
            this.disconnect();
        }
        this.setupConnection();
        this.initTerm();

        window.EventBus.$on("dialog-width-change", () => {
            if (this.enabled) {
                this.debouncedFit_();
            }
        });
    },
    beforeDestroy() {
        this.unsubscribe_.next();
        this.unsubscribe_.complete();

        if (this.conn_) {
            this.conn_.close();
        }

        if (this.connSubject_) {
            this.connSubject_.complete();
        }

        if (this.term) {
            this.term.dispose();
        }

        this.incommingMessage$_.complete();
    },
    watch: {
        container(newPod, oldPod) {
            if (this.enabled) {
                this.disconnect();
                this.setupConnection();
                this.initTerm();
            }
        },
        pod(newPod, oldPod) {
            if (this.enabled) {
                this.disconnect();
                this.setupConnection();
                this.initTerm();
            }
        },
        enabled(newPod, oldPod) {
            if (this.enabled) {
                this.disconnect();
                this.setupConnection();
                this.initTerm();
            } else {
                this.disconnect();
            }
        }
    }
};
</script>

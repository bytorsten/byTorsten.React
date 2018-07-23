import Server from './lib/Server';

const READY_FLAG = '[[READY]]';

export default class Flow {

  static terminate(error) {
    console.error(error);
    process.exit(1);
  }

  constructor(socketPath) {
    this.stop = this.stop.bind(this);

    this.server = new Server(socketPath);

    this.server.on('ready', () => {
      process.stdout.write(READY_FLAG);
    });
    this.server.on('error', Flow.terminate);
    this.server.on('command', async ({ command, data }, { reply, send }) => {
      if (!this[command]) {
        return Flow.terminate(new Error(`Unknown command ${command}`));
      }

      let replied = false;
      let result;

      try {

        result = await this[command](data, {
          reply: response => {
            replied = true;
            return reply(response);
          },
          send
        });

      } catch (error) {
        Flow.terminate(error);
      }

      if (!replied) {
        reply(result);
      }
    });
  }

  start() {
    this.server.listen();
    process.on('SIGTERM', this.stop);
  }

  async stop() {
    await this.server.close();
    process.exit(0);
  }
}

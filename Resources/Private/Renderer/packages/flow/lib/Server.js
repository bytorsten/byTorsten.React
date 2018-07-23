import { createServer } from 'net';
import { EventEmitter } from 'events';
import fs from 'fs';

import Protocol from './Protocol';

export default class Server extends EventEmitter {
  constructor(socketFile) {
    super();
    this._socketFile = socketFile;


    this._server = createServer(socket => {
      const protocol = new Protocol();
      let nextMessageId = 0;

      const messageResovlers = {};

      protocol.on('message', ({ command, data, messageId }) => {
        if (messageResovlers[command]) {
          messageResovlers[command](data);
          delete messageResovlers[command];
          return;
        }

        this.emit(
          'command',
          { command, data },
          {
            reply: messageId ? response => new Promise(resolve => {
              const payload = {
                command: messageId,
                data: typeof response === 'undefined' ? null : response
              };

              socket.write(protocol.format(payload), resolve);
            }) : null,

            send: (command, data) => new Promise(resolve => {

              const currentMessageId = `message_${++nextMessageId}`;

              const payload = {
                command,
                data,
                messageId: currentMessageId
              };

              messageResovlers[currentMessageId] = resolve;
              socket.write(protocol.format(payload));
            })
          }
        );
      });

      socket.on('data', protocol.add.bind(protocol));
    });

    let retired = false;

    this._server.once('listening', () => this.emit('ready'));

    this._server.on('error', error => {
      if (error.code === 'EADDRINUSE') {
        if (retired) {
          return this.emit('error', error);
        }

        retired = true;
        fs.unlink(error.address, error => {
          if (error) {
            return this.emit('error', error);
          }

          this.listen();
        });
      } else {
        this.emit('error', error);
      }
    });
  }

  listen() {
    this._server.listen(this._socketFile);
  }

  close() {
    return new Promise(resolve => this._server.close(resolve));
  }
}

const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const cors = require('cors');

const app = express();
app.use(cors());
app.use(express.json()); // Permite ler JSON no corpo da requisição

const server = http.createServer(app);
const io = new Server(server, {
    cors: { origin: "*" }
});

// 1. COMANDO DE CONEXÃO: Escuta quando um celular se conecta
io.on('connection', (socket) => {
    console.log(`Dispositivo conectado: ${socket.id}`);

    // 2. COMANDO DE SALA: Coloca o socket em uma sala privada (usando o ID do funcionário)
    socket.on('entrar_sala', (funcionario_id) => {
        socket.join(`funcionario_${funcionario_id}`);
        console.log(`Socket ${socket.id} entrou na sala funcionario_${funcionario_id}`);
    });

    socket.on('disconnect', () => {
        console.log('Dispositivo desconectado');
    });
});

// 3. COMANDO DE GATILHO (API): Rota que o PHP vai chamar quando validar o QR Code
app.post('/api/notificar-acesso', (req, res) => {
    const { funcionario_id, local, status } = req.body;

    if (status === 'sucesso') {
        // 4. COMANDO DE EMISSÃO: Envia a mensagem apenas para a sala daquele funcionário
        io.to(`funcionario_${funcionario_id}`).emit('acesso_liberado', {
            mensagem: 'Acesso Liberado!',
            local: local,
            hora: new Date().toLocaleTimeString()
        });
        return res.status(200).json({ success: true });
    }
    res.status(400).json({ success: false });
});

server.listen(3000, () => {
    console.log('Servidor WebSocket rodando na porta 3000');
});
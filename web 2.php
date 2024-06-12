<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebRTC Video Call</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }

        #video-call {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
        }

        video {
            width: 45%;
            height: auto;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>
    <h1>WebRTC Video Call</h1>
    <div id="video-call">
        <video id="localVideo" autoplay muted></video>
        <video id="remoteVideo" autoplay></video>
    </div>
    
    <h2>Articles</h2>
    <div id="articles">
        <h3>Article 2</h3>
        <p>Menjaga Pola Hidup Sehat Mahasiswa</p>
        <h3>Article 2</h3>
        <p>Tips Agar Fokus Belajar</p>
        <h3>Article 3</h3>
        <p>Menjaga Jam Tidur</p>
        <h3>Article 4</h3>
        <p>Menjaga Kesehatan Mental</p>
        <h3>Article 5</h3>
        <p>Makanan Hemat dan Sehat</p>
    </div>

    <h2>Recommended Videos</h2>
    <div id="videos">
        <h3>Video 1</h3>
        <video src="https://www.youtube.com/watch?v=uqGf4PWDOUw" controls></video>
        <h3>Video 2</h3>
        <video src="vhttps://www.youtube.com/watch?v=_hQm74PbME4" controls></video>
        <h3>Video 3</h3>
        <video src="videos/video2.mp4" controls></video>
        <h3>Video 4</h3>
        <video src="videos/video2.mp4" controls></video>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // WebRTC code here
            const localVideo = document.getElementById('localVideo');
            const remoteVideo = document.getElementById('remoteVideo');
            let peerConnection;
            let socket;

            const servers = {
                iceServers: [
                    {
                        urls: 'stun:stun.l.google.com:19302'
                    }
                ]
            };

            navigator.mediaDevices.getUserMedia({
                video: true,
                audio: true
            }).then(stream => {
                localVideo.srcObject = stream;
                stream.getTracks().forEach(track => peerConnection.addTrack(track, stream));
            });

            function handleICECandidateEvent(event) {
                if (event.candidate) {
                    socket.send(JSON.stringify({ iceCandidate: event.candidate }));
                }
            }

            function handleTrackEvent(event) {
                remoteVideo.srcObject = event.streams[0];
            }

            function handleNegotiationNeededEvent() {
                peerConnection.createOffer().then(offer => {
                    return peerConnection.setLocalDescription(offer);
                }).then(() => {
                    socket.send(JSON.stringify({ offer: peerConnection.localDescription }));
                }).catch(e => console.log(e));
            }

            function handleOfferMessage(data) {
                peerConnection.setRemoteDescription(new RTCSessionDescription(data.offer))
                    .then(() => peerConnection.createAnswer())
                    .then(answer => peerConnection.setLocalDescription(answer))
                    .then(() => {
                        socket.send(JSON.stringify({ answer: peerConnection.localDescription }));
                    })
                    .catch(e => console.log(e));
            }

            function handleAnswerMessage(data) {
                peerConnection.setRemoteDescription(new RTCSessionDescription(data.answer))
                    .catch(e => console.log(e));
            }

            function handleICECandidateMessage(data) {
                peerConnection.addIceCandidate(new RTCIceCandidate(data.iceCandidate))
                    .catch(e => console.log(e));
            }

            function startSignaling() {
                peerConnection = new RTCPeerConnection(servers);

                peerConnection.onicecandidate = handleICECandidateEvent;
                peerConnection.ontrack = handleTrackEvent;
                peerConnection.onnegotiationneeded = handleNegotiationNeededEvent;

                socket = new WebSocket('ws://localhost:3000');

                socket.onmessage = async (message) => {
                    const data = JSON.parse(message.data);

                    if (data.offer) {
                        handleOfferMessage(data);
                    } else if (data.answer) {
                        handleAnswerMessage(data);
                    } else if (data.iceCandidate) {
                        handleICECandidateMessage(data);
                    }
                };

                // Send a message to inform the server that this user is ready for a call
                socket.addEventListener('open', function (event) {
                    socket.send(JSON.stringify({ ready: true }));
                });
            }

            startSignaling();
        });
    </script>
</body>
</html>

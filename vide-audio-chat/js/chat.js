const PRE = "EMP"
const SUF = "CAN"
let room_id;
let getUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.mediaDevices.getUserMedia;
let local_stream;
let screenStream;
let peer = null;
let currentPeer = null
let screenSharing = false
createRoom = (chat_id, audio = true, video = true) => {
    room_id = PRE + chat_id + SUF
    let control_bar = document.getElementById('meet-controls-bar');
    control_bar.classList.remove('d-none');

    peer = new Peer(room_id);
    peer.on('open', (id) => {

      console.log('peer id is ', id);

        getUserMedia({ video: video, audio: audio }, (stream) => {

          console.log('create room local stream ', stream);

            local_stream = stream;

            setLocalStream(local_stream)
        }, (err) => {
            console.log('create room err', err)
        })
        notify("Waiting for candidate to join..")
    });



    peer.on('call', (call) => {
        call.answer(local_stream);
        call.on('stream', (stream) => {
          console.log('create room remote stream ', stream);
            setRemoteStream(stream)
        })
        currentPeer = call;
    })
}

setLocalStream = (stream) => {
    let video = document.getElementById("local-video");
    video.srcObject = stream;
    video.muted = true;
    video.play();
}

setRemoteStream = (stream) => {
    let video = document.getElementById("remote-video");
    video.srcObject = stream;
    video.play();
}

stopLocalStream = (stream) => {
    let video = document.getElementById("local-video");
    console.log('video.srcObject stop local stream before ', video.srcObject.getTracks());
    video.srcObject.getTracks().forEach((track, i) => {
      track.stop()
    });

    video.srcObject = stream;
    video.srcObject.getTracks().forEach((track, i) => {
      track.stop()
    });
    video.muted = true;
    video.play();
}

stopRemoteStream = (stream) => {
    let video = document.getElementById("remote-video");
    video.srcObject.getTracks().forEach((track, i) => {
      track.stop()
    });
    console.log('video.srcObject remote stream before ', video.srcObject);
    video.srcObject = stream;
    video.srcObject.getTracks().forEach((track, i) => {
      track.stop()
    });
    console.log('video.srcObject remote stream after ', video.srcObject);
    video.play();
}

notify = (msg) => {
    let notification = document.getElementById("notification")
    notification.innerHTML = msg
    notification.hidden = false
    setTimeout(() => {
        notification.hidden = true;
    }, 3000)
}

joinRoom = (chat_id, audio, video) => {
  console.log('audio ', audio);
  console.log('video ', video);
    room_id = PRE + chat_id + SUF
    console.log('join room id ', room_id);
    let control_bar = document.getElementById('meet-controls-bar');
    control_bar.classList.remove('d-none');

    peer = new Peer()
    peer.on('open', (id) => {
        getUserMedia({ video: true, audio: true }, (stream) => {
            local_stream = stream;
            console.log('join room local_stream ', local_stream);
            setLocalStream(local_stream)
            let call = peer.call(room_id, stream)
            call.on('stream', (stream) => {
              console.log('join room remote stream ', stream);
                setRemoteStream(stream);
            })
            currentPeer = call;
        }, (err) => {
            console.log('join error ', err)
        })

    })
}

leaveRoom = (chat_id) => {
  room_id = PRE + chat_id + SUF
  let control_bar = document.getElementById('meet-controls-bar');
  control_bar.classList.add('d-none');
  peer = new Peer();
  peer.disconnect();

  peer.on('close', (id) => {
      getUserMedia({ video: true, audio: false }, (stream) => {
          local_stream = stream;
          stopLocalStream(local_stream)
          let call = peer.call(room_id, stream)
          call.on('stream', (stream) => {
              stopRemoteStream(stream);
          })
          currentPeer = call;
      }, (err) => {
          console.log('leave err', err)
      })
  })

  peer.destroy();

  document.querySelector('#meet-controls-bar').classList.add('d-none');
  document.querySelector('.meet-area').classList.add('d-none');

  if (document.querySelector('.chat-app')) {
    document.querySelector('.chat-app').classList.remove('d-none');
  }

  if (document.querySelector('#frame')) {
    document.querySelector('#frame').classList.remove('d-none');
  }
}

startScreenShare = () => {
    if (screenSharing) {
        stopScreenSharing()
    }

    navigator.mediaDevices.getDisplayMedia({ video: true }).then((stream) => {
        screenStream = stream;
        let videoTrack = screenStream.getVideoTracks()[0];

        videoTrack.onended = () => {
            stopScreenSharing()
        }

        if (peer) {
            let sender = currentPeer.peerConnection.getSenders().find(function (s) {
                return s.track.kind == videoTrack.kind;
            })
            sender.replaceTrack(videoTrack)
            screenSharing = true
        }
        console.log('screenStream ', screenStream)
    })
}

stopScreenSharing = () => {
    if (!screenSharing) {
        return;
    }
    let videoTrack = local_stream.getVideoTracks()[0];
    if (peer) {
        let sender = currentPeer.peerConnection.getSenders().find(function (s) {
            return s.track.kind == videoTrack.kind;
        })
        sender.replaceTrack(videoTrack)
    }
    screenStream.getTracks().forEach(function (track) {
        track.stop();
    });

    screenSharing = false
}
// face-auth.js - Overhauled for maximum reliability and better diagnostics
(function() {
    'use strict';

    const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/';

    function checkEnvironment(statusElement) {
        if (typeof faceapi === 'undefined') {
            if (statusElement) statusElement.innerHTML = "<span style='color:red;'>IA non chargée. Vérifiez votre connexion.</span>";
            return false;
        }
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            if (statusElement) statusElement.innerHTML = "<span style='color:red;'>Caméra non supportée ou HTTPS requis.</span>";
            return false;
        }
        return true;
    }

    window.loadFaceApiModels = async function(statusElement) {
        if (!checkEnvironment(statusElement)) return false;
        if (statusElement) statusElement.textContent = "Chargement de l'IA...";
        try {
            if (faceapi.nets.ssdMobilenetv1.params) return true;
            await Promise.all([
                faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL),
                faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
            ]);
            if (statusElement) statusElement.textContent = "IA Prête.";
            return true;
        } catch (e) {
            console.error("Models failed:", e);
            if (statusElement) statusElement.innerHTML = "<span style='color:red;'>Erreur modèles IA.</span>";
            return false;
        }
    };

    window.toggleScanLine = function(videoElement, show) {
        if (!videoElement || !videoElement.parentElement) return;
        const scanLine = videoElement.parentElement.querySelector('.scanning-line');
        if (scanLine) scanLine.style.display = show ? 'block' : 'none';
    };

    window.startWebcam = async function(videoElement) {
        try {
            const container = videoElement.closest('.face-id-video-container') || videoElement.parentElement;
            const wrapper = document.getElementById('modal-face-container') || document.getElementById('videoWrapper');
            if (wrapper) wrapper.style.display = 'flex';
            if (container) { container.style.display = 'flex'; }

            // Required attributes
            videoElement.setAttribute('playsinline', '');
            videoElement.setAttribute('autoplay', '');
            videoElement.muted = true;

            // Mirror: user sees themselves naturally
            videoElement.style.transform = 'scaleX(-1)';
            videoElement.style.webkitTransform = 'scaleX(-1)';
            videoElement.style.width = '100%';
            videoElement.style.height = '100%';
            videoElement.style.objectFit = 'cover';

            const stream = await navigator.mediaDevices.getUserMedia({
                video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' },
                audio: false
            });

            videoElement.srcObject = stream;

            // Play and wait for first frame
            await new Promise((resolve) => {
                videoElement.oncanplay = () => videoElement.play().then(resolve).catch(resolve);
                setTimeout(resolve, 5000); // 5s fallback
            });

            window.toggleScanLine(videoElement, true);
            return stream;
        } catch (err) {
            console.error('Webcam error:', err.name, err.message);
            return null;
        }
    };

    window.stopWebcam = function(stream) {
        if (stream) stream.getTracks().forEach(track => track.stop());
    };

    window.enrollFace = async function(videoElementId, statusElementId, btnElementId) {
        const video = document.getElementById(videoElementId);
        const status = document.getElementById(statusElementId);
        const btn = document.getElementById(btnElementId);
        if (btn) btn.disabled = true;

        const modelsLoaded = await window.loadFaceApiModels(status);
        if (!modelsLoaded) { if (btn) btn.disabled = false; return; }

        const stream = await window.startWebcam(video);
        if (!stream) {
            status.innerHTML = "<span style='color:red;'>Caméra introuvable.</span>";
            if (btn) btn.disabled = false;
            return;
        }

        status.textContent = "Analyse en cours... Regardez l'écran.";
        
        // Detect loop
        let detection = null;
        for (let i = 0; i < 60; i++) {
            detection = await faceapi.detectSingleFace(video).withFaceLandmarks().withFaceDescriptor();
            if (detection) break;
            await new Promise(r => setTimeout(r, 200));
        }

        window.toggleScanLine(video, false);
        window.stopWebcam(stream);
        video.srcObject = null;

        if (!detection) {
            status.innerHTML = "<span style='color:orange;'>Échec : visage non détecté.</span>";
            if (btn) btn.disabled = false;
            return;
        }

        status.textContent = "Signature capturée ! Envoi...";
        const fd = new FormData();
        fd.append('descriptor', JSON.stringify(Array.from(detection.descriptor)));

        try {
            const response = await fetch('api.php?action=save_face', { method: 'POST', body: fd });
            const data = await response.json();
            if (data.success) {
                status.innerHTML = "<span style='color:green;'>✅ " + data.message + "</span>";
                setTimeout(() => window.location.reload(), 1500);
            } else {
                status.innerHTML = "<span style='color:red;'>❌ " + data.message + "</span>";
                if (btn) btn.disabled = false;
            }
        } catch (e) {
            status.textContent = "Erreur serveur.";
            if (btn) btn.disabled = false;
        }
    };

    window.loginWithFace = async function(videoElementId, statusElementId, btnElementId) {
        const video = document.getElementById(videoElementId);
        const status = document.getElementById(statusElementId);
        const btn = document.getElementById(btnElementId);
        if (btn) btn.disabled = true;

        const modelsLoaded = await window.loadFaceApiModels(status);
        if (!modelsLoaded) { if (btn) btn.disabled = false; return; }

        const stream = await window.startWebcam(video);
        if (!stream) {
            status.innerHTML = "<span style='color:red;'>Erreur Caméra.</span>";
            if (btn) btn.disabled = false;
            return;
        }

        status.textContent = "Identification... Ne bougez pas.";
        
        let detection = null;
        for (let i = 0; i < 60; i++) {
            detection = await faceapi.detectSingleFace(video).withFaceLandmarks().withFaceDescriptor();
            if (detection) break;
            await new Promise(r => setTimeout(r, 200));
        }

        window.toggleScanLine(video, false);
        window.stopWebcam(stream);
        video.srcObject = null;

        if (!detection) {
            status.innerHTML = "<span style='color:orange;'>Visage non reconnu. Réessayez.</span>";
            if (btn) btn.disabled = false;
            return;
        }

        status.textContent = "Vérification IA...";
        const fd = new FormData();
        fd.append('descriptor', JSON.stringify(Array.from(detection.descriptor)));

        try {
            const response = await fetch('api.php?action=face_login', { method: 'POST', body: fd });
            const data = await response.json();
            if (data.success) {
                status.innerHTML = "<span style='color:green;'>✅ Accès autorisé !</span>";
                window.location.href = data.redirect || 'nutrismart-home.html';
            } else {
                status.innerHTML = "<span style='color:red;'>❌ " + data.message + "</span>";
                if (btn) btn.disabled = false;
            }
        } catch (e) {
            status.textContent = "Erreur réseau.";
            if (btn) btn.disabled = false;
        }
    };

    window.deleteFaceID = async function(statusElementId, btnElementId) {
        if (!confirm("Supprimer votre Face ID ?")) return;
        const status = document.getElementById(statusElementId);
        const btn = document.getElementById(btnElementId);
        if (btn) btn.disabled = true;
        try {
            const response = await fetch('api.php?action=delete_face');
            const data = await response.json();
            if (data.success) {
                if (status) status.innerHTML = "<span style='color:green;'>✅ Supprimé.</span>";
                setTimeout(() => window.location.reload(), 1500);
            } else {
                if (btn) btn.disabled = false;
            }
        } catch (e) { if (btn) btn.disabled = false; }
    };
})();

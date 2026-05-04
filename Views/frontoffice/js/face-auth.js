// face-auth.js - Gère l'intelligence artificielle pour la reconnaissance faciale

/**
 * Charge les modèles TensorFlow légers depuis le CDN pour que l'IA fonctionne
 */
async function loadFaceApiModels(statusElement) {
    if (statusElement) statusElement.textContent = "Chargement des modèles d'IA (Patientez...)";

    // On utilise les modèles de base via JSDelivr CDN
    const MODEL_URL = 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights';

    try {
        await Promise.all([
            faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
        ]);
        if (statusElement) statusElement.textContent = "IA Prête. Allumez la caméra.";
        return true;
    } catch (e) {
        console.error(e);
        if (statusElement) statusElement.textContent = "Erreur de chargement des modèles IA.";
        return false;
    }
}

/**
 * Active ou désactive la ligne de scan visuelle
 */
function toggleScanLine(videoElement, show) {
    if (!videoElement || !videoElement.parentElement) return;
    const scanLine = videoElement.parentElement.querySelector('.scanning-line');
    if (scanLine) {
        scanLine.style.display = show ? 'block' : 'none';
    }
}

/**
 * Ouvre la webcam et l'attache à un élément <video>
 */
async function startWebcam(videoElement) {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        videoElement.srcObject = stream;
        toggleScanLine(videoElement, true);
        return stream;
    } catch (err) {
        console.error("Caméra bloquée ou introuvable :", err);
        alert("Impossible d'accéder à la caméra. Vérifiez vos permissions.");
        return null;
    }
}

/**
 * Arrête le flux vidéo
 */
function stopWebcam(stream) {
    if (stream) {
        const tracks = stream.getTracks();
        tracks.forEach(track => track.stop());
    }
}

/**
 * MODE: ENREGISTREMENT (Configuration initiale depuis le profil)
 */
async function enrollFace(videoElementId, statusElementId, btnElementId) {
    const video = document.getElementById(videoElementId);
    const status = document.getElementById(statusElementId);
    const btn = document.getElementById(btnElementId);

    btn.disabled = true;

    const modelsLoaded = await loadFaceApiModels(status);
    if (!modelsLoaded) return;

    status.textContent = "Démarrage caméra...";
    const stream = await startWebcam(video);
    if (!stream) return;

    status.textContent = "Regardez l'objectif... Analyse en cours (!)";

    // Attendre que la vidéo joue
    video.onplay = async () => {
        // Faire plusieurs tentatives car la 1ère image est souvent floue
        let detection = null;
        for (let i = 0; i < 50; i++) {
            detection = await faceapi.detectSingleFace(video).withFaceLandmarks().withFaceDescriptor();
            if (detection) break;
            await new Promise(r => setTimeout(r, 100)); // wait 100ms
        }

        toggleScanLine(video, false);
        stopWebcam(stream);
        video.srcObject = null;

        if (!detection) {
            status.textContent = "Visage non détecté. Réessayez avec plus de lumière.";
            btn.disabled = false;
            return;
        }

        status.textContent = "Visage capturé ! Enregistrement en base de données...";

        // Convertir le tableau Float32Array en Array classique pour JSON
        const descriptorArray = Array.from(detection.descriptor);

        const fd = new FormData();
        fd.append('descriptor', JSON.stringify(descriptorArray));

        try {
            const response = await fetch('api.php?action=save_face', { method: 'POST', body: fd });
            const data = await response.json();

            if (data.success) {
                status.textContent = "✅ " + data.message;
                status.style.color = "green";
                // Recharger la page après 2 secondes pour afficher les boutons Modifier/Supprimer
                setTimeout(() => window.location.reload(), 2000);
            } else {
                status.textContent = "❌ " + data.message;
                status.style.color = "red";
                btn.disabled = false;
            }
        } catch (e) {
            status.textContent = "Erreur réseau : " + e.message;
            btn.disabled = false;
        }
    };
}

/**
 * MODE: CONNEXION (Login)
 */
async function loginWithFace(videoElementId, statusElementId, btnElementId) {
    const video = document.getElementById(videoElementId);
    const status = document.getElementById(statusElementId);
    const btn = document.getElementById(btnElementId);

    btn.disabled = true;

    const modelsLoaded = await loadFaceApiModels(status);
    if (!modelsLoaded) return;

    status.textContent = "Démarrage caméra...";
    const stream = await startWebcam(video);
    if (!stream) return;

    status.textContent = "Analyse en cours... Veuillez ne pas bouger.";

    video.onplay = async () => {
        let detection = null;
        for (let i = 0; i < 50; i++) {
            detection = await faceapi.detectSingleFace(video).withFaceLandmarks().withFaceDescriptor();
            if (detection) break;
            await new Promise(r => setTimeout(r, 100));
        }

        toggleScanLine(video, false);
        stopWebcam(stream);
        video.srcObject = null;

        if (!detection) {
            status.textContent = "Aucun visage détecté. Cliquez pour réessayer.";
            btn.disabled = false;
            return;
        }

        status.textContent = "Identification en cours de vérification par l'IA...";
        const descriptorArray = Array.from(detection.descriptor);

        const fd = new FormData();
        fd.append('descriptor', JSON.stringify(descriptorArray));

        try {
            const response = await fetch('api.php?action=face_login', { method: 'POST', body: fd });
            const data = await response.json();

            if (data.success) {
                status.textContent = "✅ Accès accordé ! Redirection...";
                status.style.color = "green";
                window.location.href = data.redirect || 'nutrismart-home.html';
            } else {
                status.textContent = "❌" + data.message + " (Non reconnu)";
                status.style.color = "red";
                btn.disabled = false;
            }
        } catch (e) {
            console.error("Détails erreur Face ID:", e);
            status.textContent = "Erreur technique (Vérifiez la console F12).";
            btn.disabled = false;
        }
    };
}

/**
 * Supprime le Face ID de l'utilisateur
 */
async function deleteFaceID(statusElementId, btnElementId) {
    if (!confirm("Voulez-vous vraiment supprimer votre Face ID ? Vous devrez utiliser votre mot de passe pour vous connecter.")) {
        return;
    }

    const status = document.getElementById(statusElementId);
    const btn = document.getElementById(btnElementId);
    
    if (btn) btn.disabled = true;
    if (status) status.textContent = "Suppression en cours...";

    try {
        const response = await fetch('api.php?action=delete_face');
        const data = await response.json();

        if (data.success) {
            if (status) {
                status.textContent = "✅ " + data.message;
                status.style.color = "green";
            }
            // Recharger la page après un court délai pour mettre à jour l'interface
            setTimeout(() => window.location.reload(), 2000);
        } else {
            if (status) {
                status.textContent = "❌ " + data.message;
                status.style.color = "red";
            }
            if (btn) btn.disabled = false;
        }
    } catch (e) {
        if (status) status.textContent = "Erreur réseau.";
        if (btn) btn.disabled = false;
    }
}

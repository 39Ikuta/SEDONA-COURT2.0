/**
 * Sedona Court Executive PMS - Audio Engine & Reactive Stay Alarm System
 * High-reliability dual-engine: HTML5 Audio with Web Audio API Synthesizer Fallback
 */

class SedonaAudioEngine {
    constructor() {
        this.audioCtx = null;
        this.isMuted = localStorage.getItem('sedona_audio_muted') === 'true';
        this.alarmAudio = null;
        this.kitchenAudio = null;
        this.isAlarmPlaying = false;
        this.synthAlarmInterval = null;
        this.warnedRooms = new Set();
        this.snoozedRooms = {}; // { roomId: snoozeUntilTimestamp }
        this.dismissedRooms = new Set();

        this.initHtml5Audio();
        this.initAutoUnlock();
    }

    initHtml5Audio() {
        try {
            this.alarmAudio = new Audio('/sounds/alarm.mp3');
            this.alarmAudio.loop = true;
            this.alarmAudio.volume = 0.85;

            this.kitchenAudio = new Audio('/sounds/kitchen-chime.mp3');
            this.kitchenAudio.volume = 0.9;
        } catch (e) {
            console.warn('[AudioEngine] HTML5 Audio init error:', e);
        }
    }

    initAutoUnlock() {
        const unlock = () => {
            this.ensureAudioContext();
            if (this.audioCtx && this.audioCtx.state === 'suspended') {
                this.audioCtx.resume();
            }
            // Prime HTML5 audio on first user touch/click
            if (this.alarmAudio) {
                this.alarmAudio.play().then(() => {
                    this.alarmAudio.pause();
                    this.alarmAudio.currentTime = 0;
                }).catch(() => {});
            }
            window.removeEventListener('click', unlock);
            window.removeEventListener('keydown', unlock);
            window.removeEventListener('touchstart', unlock);
        };

        window.addEventListener('click', unlock, { once: true });
        window.addEventListener('keydown', unlock, { once: true });
        window.addEventListener('touchstart', unlock, { once: true });
    }

    ensureAudioContext() {
        if (!this.audioCtx) {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (AudioContextClass) {
                this.audioCtx = new AudioContextClass();
            }
        }
        if (this.audioCtx && this.audioCtx.state === 'suspended') {
            this.audioCtx.resume();
        }
    }

    toggleMute() {
        this.isMuted = !this.isMuted;
        localStorage.setItem('sedona_audio_muted', this.isMuted ? 'true' : 'false');
        if (this.isMuted) {
            this.stopDigitalAlarm();
        }
        this.updateAudioButtonState();
        return this.isMuted;
    }

    updateAudioButtonState() {
        const btn = document.getElementById('soundToggleBtn');
        const icon = document.getElementById('soundToggleIcon');
        const text = document.getElementById('soundToggleText');
        if (btn && icon && text) {
            if (this.isMuted) {
                btn.classList.remove('bg-emerald-800', 'text-emerald-100');
                btn.classList.add('bg-slate-700', 'text-slate-300');
                icon.textContent = '🔇';
                text.textContent = 'Muted';
            } else {
                btn.classList.remove('bg-slate-700', 'text-slate-300');
                btn.classList.add('bg-emerald-800', 'text-emerald-100');
                icon.textContent = '🔊';
                text.textContent = 'Sound ON';
            }
        }
    }

    /**
     * Continuous Digital Alarm for Checkout Overdue
     */
    playDigitalAlarm() {
        if (this.isMuted || this.isAlarmPlaying) return;
        this.isAlarmPlaying = true;

        // Try playing HTML5 audio file first
        if (this.alarmAudio) {
            this.alarmAudio.currentTime = 0;
            const playPromise = this.alarmAudio.play();
            if (playPromise !== undefined) {
                playPromise.catch((err) => {
                    console.warn('[AudioEngine] HTML5 alarm play blocked, falling back to Web Audio synth:', err);
                    this.startSynthesizedAlarm();
                });
            }
        } else {
            this.startSynthesizedAlarm();
        }
    }

    /**
     * Stop Continuous Digital Alarm
     */
    stopDigitalAlarm() {
        this.isAlarmPlaying = false;
        if (this.alarmAudio) {
            try {
                this.alarmAudio.pause();
                this.alarmAudio.currentTime = 0;
            } catch (e) {}
        }
        if (this.synthAlarmInterval) {
            clearInterval(this.synthAlarmInterval);
            this.synthAlarmInterval = null;
        }
    }

    /**
     * Web Audio API Synthesizer fallback for Alarm (Repeating Beeps)
     */
    startSynthesizedAlarm() {
        if (this.synthAlarmInterval) return;
        this.ensureAudioContext();

        const triggerBeep = () => {
            if (!this.isAlarmPlaying || this.isMuted) return;
            try {
                this.ensureAudioContext();
                if (!this.audioCtx) return;

                const osc = this.audioCtx.createOscillator();
                const gain = this.audioCtx.createGain();

                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(880, this.audioCtx.currentTime); // A5
                osc.frequency.exponentialRampToValueAtTime(1200, this.audioCtx.currentTime + 0.12);

                gain.gain.setValueAtTime(0.3, this.audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, this.audioCtx.currentTime + 0.15);

                osc.connect(gain);
                gain.connect(this.audioCtx.destination);

                osc.start();
                osc.stop(this.audioCtx.currentTime + 0.16);
            } catch (e) {
                console.error('[AudioEngine] Web Audio synth error:', e);
            }
        };

        triggerBeep();
        this.synthAlarmInterval = setInterval(triggerBeep, 450);
    }

    /**
     * 15-Minute Advance Notice Warning Chime (Ascending 4-tone chime)
     */
    playWarningChime() {
        if (this.isMuted) return;
        this.ensureAudioContext();

        try {
            if (this.audioCtx) {
                // Synthesize 4 ascending harmonious tones: A4 (440Hz), C#5 (554Hz), E5 (659Hz), A5 (880Hz)
                const tones = [440.00, 554.37, 659.25, 880.00];
                const now = this.audioCtx.currentTime;

                tones.forEach((freq, idx) => {
                    const osc = this.audioCtx.createOscillator();
                    const gain = this.audioCtx.createGain();

                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(freq, now + idx * 0.14);

                    gain.gain.setValueAtTime(0, now + idx * 0.14);
                    gain.gain.linearRampToValueAtTime(0.25, now + idx * 0.14 + 0.03);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + idx * 0.14 + 0.35);

                    osc.connect(gain);
                    gain.connect(this.audioCtx.destination);

                    osc.start(now + idx * 0.14);
                    osc.stop(now + idx * 0.14 + 0.36);
                });
            }
        } catch (e) {
            console.warn('[AudioEngine] Warning chime error:', e);
        }
    }

    /**
     * Kitchen Order Bell Chime (D5 -> F#5 -> A5 -> D6)
     */
    playKitchenChime() {
        if (this.isMuted) return;

        if (this.kitchenAudio) {
            this.kitchenAudio.currentTime = 0;
            this.kitchenAudio.play().catch(() => {
                this.synthesizeKitchenBell();
            });
        } else {
            this.synthesizeKitchenBell();
        }
    }

    synthesizeKitchenBell() {
        this.ensureAudioContext();
        if (!this.audioCtx) return;

        try {
            const chords = [587.33, 739.99, 880.00, 1174.66];
            const now = this.audioCtx.currentTime;

            chords.forEach((freq, idx) => {
                const osc = this.audioCtx.createOscillator();
                const gain = this.audioCtx.createGain();

                osc.type = 'triangle';
                osc.frequency.setValueAtTime(freq, now + idx * 0.12);

                gain.gain.setValueAtTime(0, now + idx * 0.12);
                gain.gain.linearRampToValueAtTime(0.3, now + idx * 0.12 + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.001, now + idx * 0.12 + 0.6);

                osc.connect(gain);
                gain.connect(this.audioCtx.destination);

                osc.start(now + idx * 0.12);
                osc.stop(now + idx * 0.12 + 0.62);
            });
        } catch (e) {}
    }

    snoozeRoom(roomId, minutes = 5) {
        this.snoozedRooms[roomId] = Date.now() + (minutes * 60 * 1000);
        this.evaluateOverdueAlarms();
    }

    dismissRoom(roomId) {
        this.dismissedRooms.add(roomId.toString());
        this.evaluateOverdueAlarms();
    }

    isRoomSnoozed(roomId) {
        const snoozeUntil = this.snoozedRooms[roomId];
        if (!snoozeUntil) return false;
        if (Date.now() < snoozeUntil) return true;
        delete this.snoozedRooms[roomId];
        return false;
    }

    isRoomDismissed(roomId) {
        return this.dismissedRooms.has(roomId.toString());
    }

    evaluateOverdueAlarms() {
        const overdueRows = document.querySelectorAll('tr[data-room-id]');
        let activeOverdueCount = 0;
        const now = Date.now();
        const activeAlarmRooms = [];

        overdueRows.forEach(row => {
            const roomId = row.getAttribute('data-room-id');
            const roomNumber = row.getAttribute('data-room-number');
            const guestName = row.getAttribute('data-guest-name');
            const checkoutTimeStr = row.getAttribute('data-checkout-time');
            const folioId = row.getAttribute('data-folio-id');

            if (!checkoutTimeStr) return;
            const checkoutTime = new Date(checkoutTimeStr).getTime();
            const remainingMs = checkoutTime - now;
            const timerCell = row.querySelector('.stay-countdown-timer');

            if (remainingMs > 0) {
                // Active stay countdown
                const totalSecs = Math.floor(remainingMs / 1000);
                const hrs = Math.floor(totalSecs / 3600);
                const mins = Math.floor((totalSecs % 3600) / 60);
                const secs = totalSecs % 60;
                const formatted = (hrs > 0 ? `${hrs}h ` : '') + `${mins}m ${secs < 10 ? '0' : ''}${secs}s`;

                if (timerCell) {
                    if (remainingMs <= 15 * 60 * 1000) {
                        timerCell.innerHTML = `<span class="text-amber-600 font-bold font-mono text-[11px] animate-pulse">⏱ ${formatted} (Due Soon)</span>`;
                    } else {
                        timerCell.innerHTML = `<span class="text-slate-700 font-mono text-[11px]">${formatted}</span>`;
                    }
                }

                // 15-Minute Warning Chime (play once per stay)
                const stayWarningKey = `warned_${roomId}_${checkoutTimeStr}`;
                if (remainingMs <= 15 * 60 * 1000 && !this.warnedRooms.has(stayWarningKey)) {
                    this.warnedRooms.add(stayWarningKey);
                    this.playWarningChime();
                }
            } else {
                // Room has reached expected checkout time (Excess Overtime)
                const overdueSecs = Math.abs(Math.floor(remainingMs / 1000));
                const overHrs = Math.floor(overdueSecs / 3600);
                const overMins = Math.floor((overdueSecs % 3600) / 60);
                const overFormatted = (overHrs > 0 ? `+${overHrs}h ` : '+') + `${overMins}m Overtime`;

                if (timerCell) {
                    timerCell.innerHTML = `<span class="text-rose-800 bg-rose-100 border border-rose-300 px-1.5 py-0.5 rounded font-bold font-mono text-[10px]">⏱ ${overFormatted}</span>`;
                }

                const isSnoozed = this.isRoomSnoozed(roomId);
                const isDismissed = this.isRoomDismissed(roomId);

                if (!isSnoozed && !isDismissed) {
                    activeOverdueCount++;
                    activeAlarmRooms.push({
                        roomId,
                        roomNumber,
                        guestName,
                        folioId,
                        overFormatted
                    });
                }
            }
        });

        // Update Alarm Banner UI
        const banner = document.getElementById('activeAlarmBanner');
        const bannerList = document.getElementById('alarmBannerRoomsList');

        if (activeOverdueCount > 0) {
            this.playDigitalAlarm();
            if (banner && bannerList) {
                banner.classList.remove('hidden');
                bannerList.innerHTML = activeAlarmRooms.map(r => `
                    <div class="flex items-center justify-between bg-white px-2.5 py-1.5 border border-purple-300 rounded shadow-sm text-xs">
                        <div>
                            <strong class="text-purple-950 font-mono text-sm">ROOM ${r.roomNumber}</strong> &bull;
                            <span class="text-slate-800 font-medium">${r.guestName}</span> &bull;
                            <span class="text-purple-800 font-bold font-mono">${r.overFormatted}</span>
                        </div>
                        <div class="flex items-center space-x-1.5">
                            <button onclick="sedonaAudio.snoozeRoom(${r.roomId}, 5)" class="px-2 py-0.5 bg-amber-600 hover:bg-amber-700 text-white font-bold text-[10px] rounded">
                                Snooze 5m
                            </button>
                            <button onclick="sedonaAudio.dismissRoom(${r.roomId})" class="px-2 py-0.5 bg-slate-600 hover:bg-slate-700 text-white font-bold text-[10px] rounded">
                                Silence
                            </button>
                            <a href="/checkout/${r.folioId}" class="px-2.5 py-0.5 bg-rose-700 hover:bg-rose-800 text-white font-bold text-[10px] rounded">
                                Settle Checkout
                            </a>
                        </div>
                    </div>
                `).join('');
            }
        } else {
            this.stopDigitalAlarm();
            if (banner) {
                banner.classList.add('hidden');
            }
        }
    }
}

// Global instance
window.sedonaAudio = new SedonaAudioEngine();

// Auto-run reactive loop every 1,000ms on pages with room countdowns
setInterval(() => {
    if (window.sedonaAudio) {
        window.sedonaAudio.evaluateOverdueAlarms();
    }
}, 1000);

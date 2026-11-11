document.addEventListener('DOMContentLoaded', () => {
    init3DScene();
});

function init3DScene() {
    const canvas = document.getElementById('webgl-canvas');
    if (!canvas) return;

    const heroWrapper = document.querySelector('.hero-3d-wrapper');
    let glowUpdateFrame = null;
    const updateHeroGlow = () => {
        if (!heroWrapper) return;
        const bounds = heroWrapper.getBoundingClientRect();
        canvas.style.setProperty('--hero-glow-x', `${bounds.left + bounds.width / 2}px`);
        canvas.style.setProperty('--hero-glow-y', `${bounds.top + bounds.height / 2}px`);
    };
    const scheduleHeroGlowUpdate = () => {
        if (glowUpdateFrame !== null) return;
        glowUpdateFrame = window.requestAnimationFrame(() => {
            glowUpdateFrame = null;
            updateHeroGlow();
        });
    };

    updateHeroGlow();
    window.addEventListener('scroll', scheduleHeroGlowUpdate, { passive: true });

    if (typeof THREE === 'undefined' || typeof gsap === 'undefined') return;

    const isMobile = window.innerWidth < 768;

    // ==========================================
    // ⚙️ CONFIGURACIÓN DE TAMAÑO Y POSICIÓN
    // Ajusta los valores de Escritorio y Celular a tu gusto:
    // ==========================================
    const CONFIG = isMobile ? {
        // CONFIGURACIÓN PARA MÓVILES (Pantallas chicas)
        scale: {
            hero: 0.7,       // Tamaño de la torta al inicio en celular
            destacados: 1.1, // Tamaño en la sección TOP / Reseñas (vistas desde arriba)
            nosotros: 0.7,   // Tamaño en la sección Nosotros
            reseñas: 1.1     // Tamaño en Reseñas
        },
        position: {
            heroX: 0,        // Centrado en celulares
            heroY: -0.2,
            destacadosY: 0,
            nosotrosY: 0,
            reseñasY: 0
        }
    } : {
        // CONFIGURACIÓN PARA ESCRITORIO (Pantallas grandes)
        scale: {
            hero: 2.4,       
            destacados: 4.5, 
            nosotros: 1.9,   
            reseñas: 4.5     
        },
        position: {
            heroX: 3.2,      // Desplazado a la derecha en PC
            heroY: 0,        
            destacadosY: 0.1,
            nosotrosY: -0.2,
            reseñasY: 0.1
        }
    };

    // 1. Escena, Cámara y Renderizador
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.set(0, 0, 9);

    const renderer = new THREE.WebGLRenderer({
        canvas: canvas,
        alpha: true,
        antialias: true,
        powerPreference: 'high-performance'
    });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

    // 2. Iluminación
    scene.add(new THREE.AmbientLight(0xf2e6f7, 1.8));

    const keyLight = new THREE.DirectionalLight(0xffd1da, 3.0);
    keyLight.position.set(6, 10, 6);
    scene.add(keyLight);

    const fillLight = new THREE.DirectionalLight(0xd4bee4, 1.5);
    fillLight.position.set(-6, -2, -4);
    scene.add(fillLight);

    // 3. Grupos 3D
    const cakeGroup = new THREE.Group();
    const tiltGroup = new THREE.Group();
    const spinGroup = new THREE.Group();

    tiltGroup.add(spinGroup);
    cakeGroup.add(tiltGroup);
    scene.add(cakeGroup);

    const loader = new THREE.GLTFLoader();
    const modelPath = '/models/modelo-reposteria.glb';
    const fallbackPath = '/models/torta.glb';

    function loadModel(path, fallback) {
        loader.load(path, (gltf) => {
            const model = gltf.scene;
            const box = new THREE.Box3().setFromObject(model);
            model.position.sub(box.getCenter(new THREE.Vector3()));
            spinGroup.add(model);

            // Estado Inicial (Hero) aplicando la configuración definida arriba
            cakeGroup.scale.set(CONFIG.scale.hero, CONFIG.scale.hero, CONFIG.scale.hero);
            cakeGroup.position.set(CONFIG.position.heroX, CONFIG.position.heroY, 0);
            tiltGroup.rotation.set(0.22, 0, 0);

            setupScrollAnimations(cakeGroup, tiltGroup);
        }, undefined, () => {
            if (fallback) loadModel(fallback, null);
        });
    }

    loadModel(modelPath, fallbackPath);

    // Rotación continua
    function animate() {
        requestAnimationFrame(animate);
        if (spinGroup) {
            spinGroup.rotation.y += 0.005;
        }
        renderer.render(scene, camera);
    }
    animate();

    // Recalcular si el usuario rota la pantalla o redimensiona
    window.addEventListener('resize', () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
        scheduleHeroGlowUpdate();
    });

    // Animaciones por Scroll adaptadas
    function setupScrollAnimations(mainGroup, tilt) {
        gsap.registerPlugin(ScrollTrigger);

        // 1. SECCIÓN TOP (#destacados)
        if (document.getElementById('destacados')) {
            gsap.timeline({
                scrollTrigger: {
                    trigger: '#destacados',
                    start: 'top bottom',
                    end: 'top center',
                    scrub: 1.2,
                }
            })
            .to(tilt.rotation, { x: Math.PI / 2, duration: 1 }, 0) 
            .to(mainGroup.position, { x: 0, y: CONFIG.position.destacadosY, z: -1.5, duration: 1 }, 0) 
            .to(mainGroup.scale, { x: CONFIG.scale.destacados, y: CONFIG.scale.destacados, z: CONFIG.scale.destacados }, 0);
        }

        // 2. SECCIÓN NOSOTROS (#nosotros)
        if (document.getElementById('nosotros')) {
            gsap.timeline({
                scrollTrigger: {
                    trigger: '#nosotros',
                    start: 'top bottom',
                    end: 'top center',
                    scrub: 1.2,
                }
            })
            .to(tilt.rotation, { x: 0.22, duration: 1 }, 0) 
            .to(mainGroup.position, { x: 0, y: CONFIG.position.nosotrosY, z: 0, duration: 1 }, 0)
            .to(mainGroup.scale, { x: CONFIG.scale.nosotros, y: CONFIG.scale.nosotros, z: CONFIG.scale.nosotros }, 0);
        }

        // 3. BANNER CUESTIONARIO (#quiz-banner)
        if (document.getElementById('quiz-banner')) {
            gsap.timeline({
                scrollTrigger: {
                    trigger: '#quiz-banner',
                    start: 'top bottom',
                    end: 'top center',
                    scrub: 1,
                }
            })
            .to(mainGroup.scale, { x: 0.001, y: 0.001, z: 0.001, duration: 0.8 }, 0);
        }

        // 4. SECCIÓN RESEÑAS (#resenas)
        if (document.getElementById('resenas')) {
            gsap.timeline({
                scrollTrigger: {
                    trigger: '#resenas',
                    start: 'top bottom',
                    end: 'top center',
                    scrub: 1.2,
                }
            })
            .to(mainGroup.scale, { x: CONFIG.scale.reseñas, y: CONFIG.scale.reseñas, z: CONFIG.scale.reseñas, duration: 1 }, 0)
            .to(tilt.rotation, { x: Math.PI / 2, duration: 1 }, 0) 
            .to(mainGroup.position, { x: 0, y: CONFIG.position.reseñasY, z: -1.5, duration: 1 }, 0); 
        }

        // 5. FOOTER / FINAL
        if (document.querySelector('footer')) {
            gsap.timeline({
                scrollTrigger: {
                    trigger: 'footer',
                    start: 'top bottom',
                    end: 'top top',
                    scrub: 1,
                }
            })
            .to(mainGroup.scale, { x: 0.001, y: 0.001, z: 0.001, duration: 0.5 }, 0);
        }
    }
}
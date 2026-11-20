import * as THREE from 'https://cdn.jsdelivr.net/npm/three@0.187.0/+esm';
import {
    parseDxf,
    createThreeObjectsFromDXF,
    createRenderer,
    useCamera,
    useOrbitControls,
} from 'https://cdn.jsdelivr.net/npm/dxf-render@1.7.2/+esm';

const FRUSTUM_SIZE = 100;

function initViewer(container) {
    const source = container.querySelector('script.dxf-source');
    if (!source || container.dataset.dxfInitialized === 'true') return;
    container.dataset.dxfInitialized = 'true';

    const errorPrefix = container.dataset.error || 'DXF rendering error: ';

    (async () => {
        const dxfText = JSON.parse(source.textContent);
        const dxf = parseDxf(dxfText);
        const { group } = await createThreeObjectsFromDXF(dxf);

        const width = Math.max(container.clientWidth, 1);
        const height = Math.max(container.clientHeight, 1);
        const aspect = width / height;
        const camera = new THREE.OrthographicCamera(
            (-FRUSTUM_SIZE * aspect) / 2,
            (FRUSTUM_SIZE * aspect) / 2,
            FRUSTUM_SIZE / 2,
            -FRUSTUM_SIZE / 2,
            0.1,
            1000
        );

        const scene = new THREE.Scene();
        scene.background = new THREE.Color(0xf5f5f5);
        scene.add(group);

        const renderer = createRenderer({ aaMode: 'msaa' });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        renderer.setSize(width, height);

        container.textContent = '';
        container.appendChild(renderer.domElement);

        const { fitCameraToBox } = useCamera();
        const { initControls } = useOrbitControls();
        const controls = initControls(camera, renderer.domElement);
        controls.addEventListener('change', () => renderer.render(scene, camera));

        fitCameraToBox(new THREE.Box3().setFromObject(group), camera);
        renderer.render(scene, camera);

        const resize = () => {
            const w = Math.max(container.clientWidth, 1);
            const h = Math.max(container.clientHeight, 1);
            const a = w / h;

            camera.left = (-FRUSTUM_SIZE * a) / 2;
            camera.right = (FRUSTUM_SIZE * a) / 2;
            camera.top = FRUSTUM_SIZE / 2;
            camera.bottom = -FRUSTUM_SIZE / 2;
            camera.updateProjectionMatrix();

            renderer.setSize(w, h);
            renderer.render(scene, camera);
        };

        if (typeof ResizeObserver !== 'undefined') {
            new ResizeObserver(resize).observe(container);
        } else {
            window.addEventListener('resize', resize);
        }
    })().catch((error) => {
        container.textContent = '';

        const message = document.createElement('p');
        message.className = 'dxf-error';
        message.textContent = errorPrefix + (error && error.message ? error.message : String(error));
        container.appendChild(message);
    });
}

document.querySelectorAll('.dxf-container').forEach(initViewer);

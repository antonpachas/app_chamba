// SVG placeholder local — no requiere red ni servidor externo.
const SVG = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 480" fill="none">
  <rect width="800" height="480" fill="#e8eef6"/>
  <rect x="330" y="170" width="140" height="100" rx="12" fill="#c3d4e8"/>
  <circle cx="370" cy="205" r="20" fill="#a0b8d4"/>
  <path d="M330 270 L380 215 L420 248 L460 210 L470 270Z" fill="#b8cfe4"/>
  <rect x="350" y="235" width="40" height="35" rx="4" fill="#8aa8c8"/>
</svg>`;

export const LISTING_PLACEHOLDER = `data:image/svg+xml,${encodeURIComponent(SVG)}`;

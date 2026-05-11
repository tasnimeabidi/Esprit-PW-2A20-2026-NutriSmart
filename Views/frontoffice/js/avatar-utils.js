/**
 * NutriSmart Avatar Utility
 * Provides unique, consistent colors for user avatars based on their name.
 */

const getAvatarStyle = (name) => {
    if (!name) return { bg: '#e8f0e9', text: '#5a7060' };

    const palette = [
        { bg: '#F1F8E9', text: '#091247ff' }, // Light Green
        { bg: '#E3F2FD', text: '#1b60afff' }, // Light Blue
        { bg: '#FFF3E0', text: '#e69e78ff' }, // Light Orange
        { bg: '#F3E5F5', text: '#923cb7ff' }, // Light Purple
        { bg: '#FFEBEE', text: '#b42121ff' }, // Light Red
        { bg: '#E0F2F1', text: '#1f8478ff' }, // Light Teal
        { bg: '#FFFDE7', text: '#8f6522ff' }, // Light Yellow
        { bg: '#EFEBE9', text: '#4E342E' }, // Light Brown
        { bg: '#FCE4EC', text: '#AD1457' }, // Light Pink
        { bg: '#E8EAF6', text: '#e37c9eff' }  // Light Indigo
    ];
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    const index = Math.abs(hash) % palette.length;
    return palette[index];
};

// Legacy support for existing code that expects a single color string
const getAvatarColor = (name) => {
    return getAvatarStyle(name).bg;
};

// Global assignment for availability
window.getAvatarStyle = getAvatarStyle;
window.getAvatarColor = getAvatarColor;

declare global {
  interface Window {
    AMapLoader: {
      load: (options: { key: string; version: string; plugins?: string[] }) => Promise<typeof AMap>;
    };
    AMap: typeof AMap;
  }
}

// eslint-disable-next-line @typescript-eslint/no-unused-vars
export declare namespace AMap {
  class Map {
    constructor(container: string | HTMLElement, options?: MapOptions);
    add(overlay: Overlay | Overlay[]): void;
    // ... 其他 Map 方法
  }

  interface MapOptions {
    zoom?: number;
    center?: LngLat | [number, number];
    viewmode?: '2D' | '3D';
    mapStyle?: string;
    // ... 其他 MapOptions
  }

  class LngLat {
    constructor(longitude: number, latitude: number);
  }

  class Marker {
    constructor(options: MarkerOptions);
    setContent(content: string | HTMLElement): void; // 添加 setContent 方法
    setIcon(icon: Icon | string): void; // 添加 setIcon 方法
    setAnchor(anchor: string): void; // 添加 setAnchor 方法
  }

  interface MarkerOptions {
    position: LngLat | [number, number];
    icon?: Icon | string;
    title?: string;
    anchor?: string; // 添加 anchor 属性
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    extData?: any; // 保持any，因为extData可以是任意类型
    // ... 其他 MarkerOptions
  }

  class Icon {
    constructor(options: IconOptions);
    getImage(): string; // 添加 getImage 方法
    getSize(): Size; // 添加 getSize 方法
  }

  interface IconOptions {
    size: Size;
    image: string;
    imageSize: Size;
    // ... 其他 IconOptions
  }

  class Size {
    constructor(width: number, height: number);
    getWidth(): number; // 添加 getWidth 方法
    getHeight(): number; // 添加 getHeight 方法
  }

  type Overlay = Marker; // 可以扩展为其他覆盖物类型

  // MarkerClusterer 类型定义
  class MarkerClusterer {
    constructor(map: Map, markers: Marker[], options?: MarkerClustererOptions);
  }

  interface MarkerClustererOptions {
    gridSize?: number;
    maxZoom?: number;
    renderClusterMarker?: (context: ClusterRenderContext) => void;
    renderMarker?: (context: MarkerRenderContext) => void;
    // ... 其他 MarkerClustererOptions
  }

  interface ClusterRenderContext {
    count: number;
    marker: Marker;
    // ... 其他聚合点渲染上下文属性
  }

  interface MarkerRenderContext {
    marker: Marker;
    // ... 其他单个标记点渲染上下文属性
  }
}

export {};

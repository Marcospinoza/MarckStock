CREATE TABLE IF NOT EXISTS configuracion (
    clave VARCHAR(100) PRIMARY KEY,
    valor TEXT NOT NULL,
    actualizado_en TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS usuarios (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    usuario VARCHAR(80) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol VARCHAR(20) NOT NULL CHECK (rol IN ('ADMIN', 'ENCARGADO')),
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    fecha_creacion TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    ultimo_acceso TIMESTAMPTZ
);

CREATE TABLE IF NOT EXISTS categorias (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion VARCHAR(255),
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    fecha_creacion TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS productos (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    categoria_id BIGINT NOT NULL REFERENCES categorias(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    precio NUMERIC(10,2) NOT NULL CHECK (precio >= 0),
    stock INTEGER NOT NULL CHECK (stock >= 0),
    stock_minimo INTEGER NOT NULL DEFAULT 5 CHECK (stock_minimo >= 0),
    fecha_registro DATE NOT NULL DEFAULT CURRENT_DATE,
    creado_en TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    actualizado_en TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS movimientos_stock (
    id BIGSERIAL PRIMARY KEY,
    producto_id BIGINT NOT NULL
        REFERENCES productos(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    usuario_id BIGINT NOT NULL
        REFERENCES usuarios(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    tipo VARCHAR(10) NOT NULL
        CHECK (tipo IN ('ENTRADA', 'SALIDA')),

    cantidad INTEGER NOT NULL
        CHECK (cantidad > 0),

    motivo VARCHAR(100) NOT NULL,
    observacion VARCHAR(255),

    stock_anterior INTEGER NOT NULL
        CHECK (stock_anterior >= 0),

    stock_nuevo INTEGER NOT NULL
        CHECK (stock_nuevo >= 0),

    fecha TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS api_tokens (
    id BIGSERIAL PRIMARY KEY,
    usuario_id BIGINT NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expira_en TIMESTAMPTZ NOT NULL,
    creado_en TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_productos_nombre ON productos(nombre);
CREATE INDEX IF NOT EXISTS idx_productos_categoria ON productos(categoria_id);
CREATE INDEX IF NOT EXISTS idx_productos_stock ON productos(stock);
CREATE INDEX IF NOT EXISTS idx_movimientos_producto
ON movimientos_stock(producto_id);

CREATE INDEX IF NOT EXISTS idx_movimientos_usuario
ON movimientos_stock(usuario_id);

CREATE INDEX IF NOT EXISTS idx_movimientos_fecha
ON movimientos_stock(fecha);

CREATE INDEX IF NOT EXISTS idx_movimientos_tipo
ON movimientos_stock(tipo);
CREATE INDEX IF NOT EXISTS idx_tokens_usuario ON api_tokens(usuario_id);


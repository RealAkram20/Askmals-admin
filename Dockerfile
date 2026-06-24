# Step 1: Build the application
FROM node:20-alpine AS builder
WORKDIR /app

# Copy lockfiles and install dependencies
COPY package*.json ./
RUN npm ci --force

# Copy the rest of your application code and build
COPY . .
RUN npm run build

# Step 2: Serve the application
FROM node:20-alpine AS runner
WORKDIR /app

# Install a simple static server (or adjust this if you are running a custom Node server)
RUN npm install -g serve

# Copy built assets from the builder stage
COPY --from=builder /app/dist ./dist

EXPOSE 3000
CMD ["serve", "-s", "dist", "-l", "3000"]
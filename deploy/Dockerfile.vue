FROM node:18-alpine AS builder # Set working directory WORKDIR /app # Copy
package files COPY package*.json ./ # Install dependencies RUN npm ci
--only=production # Copy source code COPY . . # Build the application RUN npm
run build # Production stage FROM node:18-alpine AS production # Install global
packages RUN npm install -g serve # Set working directory WORKDIR /app # Copy
built application from builder stage COPY --from=builder /app/dist ./dist COPY
--from=builder /app/package*.json ./ # Create non-root user RUN addgroup -g 1001
-S nodejs RUN adduser -S nextjs -u 1001 # Change ownership RUN chown -R
nextjs:nodejs /app USER nextjs # Expose port EXPOSE 3000 # Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \ CMD
curl -f http://localhost:3000 || exit 1 # Start the application CMD ["serve",
"-s", "dist", "-l", "3000"]

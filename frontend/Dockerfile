FROM node:20

WORKDIR /app

COPY . .
# ... after COPY . .
RUN ls -R src/components/Admin  # This will print the files in your logs
RUN npm run build
RUN npm install
RUN npm run build

CMD ["npm", "run", "preview"]